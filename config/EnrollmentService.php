<?php

function assignGradeSubjects($db, $enrollment_id, $grade_level) {
    $stmt = $db->prepare("SELECT id FROM subjects WHERE grade_level = ? AND status = 'active'");
    $stmt->bind_param("i", $grade_level);
    $stmt->execute();
    $subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($subjects as $subject) {
        $insert = $db->prepare("INSERT IGNORE INTO student_subjects (enrollment_id, subject_id, status) VALUES (?, ?, 'enrolled')");
        $insert->bind_param("ii", $enrollment_id, $subject['id']);
        $insert->execute();
    }

    return count($subjects);
}

function createPaymentSchedules($db, $enrollment_id, $net_amount) {
    $types = ['Prelim', 'Midterm', 'Pre-Final', 'Final'];
    $installment = $net_amount / 4;
    $due_offsets = [30, 60, 90, 120];

    foreach ($types as $index => $type) {
        $due_date = date('Y-m-d', strtotime('+' . $due_offsets[$index] . ' days'));
        $stmt = $db->prepare("INSERT INTO payment_schedules (enrollment_id, payment_type, amount_due, amount_paid, payment_status, due_date) VALUES (?, ?, ?, 0, 'pending', ?)");
        $stmt->bind_param("isds", $enrollment_id, $type, $installment, $due_date);
        $stmt->execute();
    }
}

function getUnpaidSchedules($db, $enrollment_id) {
    $stmt = $db->prepare("SELECT * FROM payment_schedules WHERE enrollment_id = ? AND payment_status != 'paid' ORDER BY id ASC");
    $stmt->bind_param("i", $enrollment_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function distributeToUnpaidSchedules($db, $enrollment_id, $amount, $operation = 'add') {
    $unpaid = getUnpaidSchedules($db, $enrollment_id);

    if (count($unpaid) === 0) {
        return 0;
    }

    $split = $amount / count($unpaid);
    foreach ($unpaid as $schedule) {
        $new_due = $operation === 'add'
            ? $schedule['amount_due'] + $split
            : max(0, $schedule['amount_due'] - $split);

        $upd = $db->prepare("UPDATE payment_schedules SET amount_due = ? WHERE id = ?");
        $upd->bind_param("di", $new_due, $schedule['id']);
        $upd->execute();
    }

    return count($unpaid);
}

function createEnrollment($db, $student_id, $academic_year, $semester, $grade_level, $total_tuition, $user_id) {
    $check = $db->prepare("SELECT id FROM enrollments WHERE student_id = ? AND academic_year = ? AND semester = ?");
    $check->bind_param("isi", $student_id, $academic_year, $semester);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        throw new Exception('Student is already enrolled for this semester.');
    }

    $stmt = $db->prepare("INSERT INTO enrollments (student_id, academic_year, semester, grade_level, total_tuition, net_amount, enrollment_status, enrolled_at) VALUES (?, ?, ?, ?, ?, ?, 'enrolled', NOW())");
    $stmt->bind_param("isiidd", $student_id, $academic_year, $semester, $grade_level, $total_tuition, $total_tuition);
    $stmt->execute();
    $enrollment_id = $stmt->insert_id;

    createPaymentSchedules($db, $enrollment_id, $total_tuition);
    assignGradeSubjects($db, $enrollment_id, $grade_level);

    $is_continuing = $db->prepare("SELECT COUNT(*) as cnt FROM enrollments WHERE student_id = ? AND id != ?");
    $is_continuing->bind_param("ii", $student_id, $enrollment_id);
    $is_continuing->execute();
    $count = $is_continuing->get_result()->fetch_assoc()['cnt'];
    $student_status = $count > 0 ? 'continuing' : 'enrolled';

    $upd = $db->prepare("UPDATE students SET status = ? WHERE id = ?");
    $upd->bind_param("si", $student_status, $student_id);
    $upd->execute();

    logActivity($db, $user_id, 'Enrollment Created', 'enrollment', $enrollment_id, "Grade {$grade_level}, {$academic_year} Sem {$semester}");

    return $enrollment_id;
}

function recordStudentPayment($db, $schedule_id, $amount_paid, $payment_method, $reference_number, $notes, $user_id) {
    $stmt = $db->prepare("SELECT ps.*, e.id as enrollment_id FROM payment_schedules ps JOIN enrollments e ON ps.enrollment_id = e.id WHERE ps.id = ?");
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $schedule = $stmt->get_result()->fetch_assoc();

    if (!$schedule) {
        throw new Exception('Payment schedule not found.');
    }

    $new_paid = $schedule['amount_paid'] + $amount_paid;
    $new_status = ($new_paid >= $schedule['amount_due']) ? 'paid' : 'partial';

    $upd = $db->prepare("UPDATE payment_schedules SET amount_paid = ?, payment_status = ?, paid_at = NOW() WHERE id = ?");
    $upd->bind_param("dsi", $new_paid, $new_status, $schedule_id);
    $upd->execute();

    $ins = $db->prepare("INSERT INTO payments (payment_schedule_id, amount_paid, payment_method, reference_number, paid_by, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $ins->bind_param("idssis", $schedule_id, $amount_paid, $payment_method, $reference_number, $user_id, $notes);
    $ins->execute();
    $payment_id = $ins->insert_id;

    $verify_col = $db->query("SHOW COLUMNS FROM payments LIKE 'verification_status'");
    if ($verify_col && $verify_col->num_rows > 0) {
        $db->query("UPDATE payments SET verification_status = 'pending' WHERE id = " . (int)$payment_id);
    }

    syncExamEligibility($db, $schedule['enrollment_id'], $schedule['payment_type'], $user_id);

    logActivity($db, $user_id, 'Payment Recorded', 'payment', $payment_id, "{$schedule['payment_type']}: ₱{$amount_paid}");

    return [
        'payment_id' => $payment_id,
        'new_status' => $new_status,
        'amount_paid' => $new_paid,
        'balance' => max(0, $schedule['amount_due'] - $new_paid),
        'payment_type' => $schedule['payment_type'],
        'enrollment_id' => $schedule['enrollment_id']
    ];
}

function syncExamEligibility($db, $enrollment_id, $payment_type, $user_id) {
    $eligible = getExamEligibilityByPayment($db, $enrollment_id, $payment_type) ? 1 : 0;

    $check = $db->prepare("SELECT id FROM exam_eligibility WHERE enrollment_id = ? AND exam_period = ?");
    $check->bind_param("is", $enrollment_id, $payment_type);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();

    if ($existing) {
        $upd = $db->prepare("UPDATE exam_eligibility SET is_eligible = ?, checked_by = ?, checked_at = NOW() WHERE id = ?");
        $upd->bind_param("iii", $eligible, $user_id, $existing['id']);
        $upd->execute();
    } else {
        $ins = $db->prepare("INSERT INTO exam_eligibility (enrollment_id, exam_period, is_eligible, checked_by, checked_at) VALUES (?, ?, ?, ?, NOW())");
        $ins->bind_param("isii", $enrollment_id, $payment_type, $eligible, $user_id);
        $ins->execute();
    }
}

function addAdditionalFee($db, $enrollment_id, $fee_description, $fee_amount, $applicable_grade, $user_id) {
    $stmt = $db->prepare("SELECT * FROM enrollments WHERE id = ?");
    $stmt->bind_param("i", $enrollment_id);
    $stmt->execute();
    $enrollment = $stmt->get_result()->fetch_assoc();
    if (!$enrollment) {
        throw new Exception('Enrollment not found.');
    }

    $fee = $db->prepare("INSERT INTO additional_fees (enrollment_id, fee_description, fee_amount, applicable_grade, created_by) VALUES (?, ?, ?, ?, ?)");
    $fee->bind_param("isdii", $enrollment_id, $fee_description, $fee_amount, $applicable_grade, $user_id);
    $fee->execute();

    $new_net = $enrollment['net_amount'] + $fee_amount;
    $upd = $db->prepare("UPDATE enrollments SET additional_fees = additional_fees + ?, net_amount = ? WHERE id = ?");
    $upd->bind_param("ddi", $fee_amount, $new_net, $enrollment_id);
    $upd->execute();

    distributeToUnpaidSchedules($db, $enrollment_id, $fee_amount, 'add');

    logActivity($db, $user_id, 'Additional Fee Added', 'enrollment', $enrollment_id, "{$fee_description}: ₱{$fee_amount}");

    return $new_net;
}

function applyAdditionalFeeByGrade($db, $grade_level, $fee_description, $fee_amount, $user_id) {
    $stmt = $db->prepare("SELECT id FROM enrollments WHERE grade_level = ? AND enrollment_status IN ('pending','verified','enrolled')");
    $stmt->bind_param("i", $grade_level);
    $stmt->execute();
    $enrollments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $count = 0;

    foreach ($enrollments as $enrollment) {
        addAdditionalFee($db, $enrollment['id'], $fee_description, $fee_amount, $grade_level, $user_id);
        $count++;
    }

    return $count;
}

function createScholarshipApplication($db, $student_id, $scholarship_id, $enrollment_id, $user_id) {
    $prog = $db->prepare("SELECT * FROM scholarships WHERE id = ? AND status = 'active'");
    $prog->bind_param("i", $scholarship_id);
    $prog->execute();
    $scholarship = $prog->get_result()->fetch_assoc();
    if (!$scholarship) {
        throw new Exception('Scholarship program not found.');
    }

    $enroll = $db->prepare("SELECT * FROM enrollments WHERE id = ? AND student_id = ?");
    $enroll->bind_param("ii", $enrollment_id, $student_id);
    $enroll->execute();
    $enrollment = $enroll->get_result()->fetch_assoc();
    if (!$enrollment) {
        throw new Exception('Enrollment not found for this student.');
    }

    $dup = $db->prepare("SELECT id FROM student_scholarships WHERE student_id = ? AND scholarship_id = ? AND enrollment_id = ? AND status IN ('pending','active')");
    $dup->bind_param("iii", $student_id, $scholarship_id, $enrollment_id);
    $dup->execute();
    if ($dup->get_result()->num_rows > 0) {
        throw new Exception('Student already has this scholarship for this enrollment.');
    }

    $approved_amount = $scholarship['discount_amount'] ?? 0;
    if ($scholarship['discount_percentage']) {
        $approved_amount = $enrollment['net_amount'] * ($scholarship['discount_percentage'] / 100);
    }
    if ($approved_amount <= 0) {
        throw new Exception('Could not calculate scholarship amount.');
    }

    $ins = $db->prepare("INSERT INTO student_scholarships (student_id, scholarship_id, enrollment_id, approved_amount, status) VALUES (?, ?, ?, ?, 'pending')");
    $ins->bind_param("iiid", $student_id, $scholarship_id, $enrollment_id, $approved_amount);
    $ins->execute();
    $id = $ins->insert_id;

    logActivity($db, $user_id, 'Scholarship Applied', 'scholarship', $id, "Amount: ₱{$approved_amount}");

    return ['id' => $id, 'approved_amount' => $approved_amount];
}

function approveScholarship($db, $student_scholarship_id, $approved, $user_id, $notes = '') {
    $stmt = $db->prepare("SELECT ss.*, e.id as enrollment_id FROM student_scholarships ss JOIN enrollments e ON ss.enrollment_id = e.id WHERE ss.id = ?");
    $stmt->bind_param("i", $student_scholarship_id);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();
    if (!$record) {
        throw new Exception('Scholarship record not found.');
    }

    if ($approved) {
        $status = 'active';
        $upd = $db->prepare("UPDATE student_scholarships SET status = ?, approved_by = ?, approved_date = NOW() WHERE id = ?");
        $upd->bind_param("sii", $status, $user_id, $student_scholarship_id);
        $upd->execute();

        $deduction = $record['approved_amount'];
        $enroll_upd = $db->prepare("UPDATE enrollments SET scholarship_amount = scholarship_amount + ?, net_amount = net_amount - ? WHERE id = ?");
        $enroll_upd->bind_param("ddi", $deduction, $deduction, $record['enrollment_id']);
        $enroll_upd->execute();

        distributeToUnpaidSchedules($db, $record['enrollment_id'], $deduction, 'subtract');

        logActivity($db, $user_id, 'Scholarship Approved', 'scholarship', $student_scholarship_id, $notes);
    } else {
        $status = 'cancelled';
        $upd = $db->prepare("UPDATE student_scholarships SET status = ?, approved_by = ?, approved_date = NOW() WHERE id = ?");
        $upd->bind_param("sii", $status, $user_id, $student_scholarship_id);
        $upd->execute();
        logActivity($db, $user_id, 'Scholarship Rejected', 'scholarship', $student_scholarship_id, $notes);
    }
}

function verifyPayment($db, $payment_id, $user_id) {
    $upd = $db->prepare("UPDATE payments SET verification_status = 'verified', verified_by = ?, verified_at = NOW() WHERE id = ?");
    $upd->bind_param("ii", $user_id, $payment_id);
    if (!$upd->execute()) {
        throw new Exception('Failed to verify payment. Run database-update.sql if this is an older database.');
    }

    logActivity($db, $user_id, 'Payment Verified', 'payment', $payment_id, 'Assessment confirmed payment');
}

function ensureDatabaseSchema($db) {
    $result = $db->query("SHOW COLUMNS FROM payments LIKE 'verification_status'");
    if ($result && $result->num_rows === 0) {
        @$db->query("ALTER TABLE payments ADD COLUMN verification_status ENUM('pending', 'verified') DEFAULT 'pending' AFTER notes");
        @$db->query("ALTER TABLE payments ADD COLUMN verified_by INT NULL AFTER verification_status");
        @$db->query("ALTER TABLE payments ADD COLUMN verified_at TIMESTAMP NULL AFTER verified_by");
    }
}

function getDocumentProgress($db, $student_id) {
    $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(status = 'verified') as verified FROM documents WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getEnrollmentSubjects($db, $enrollment_id) {
    $stmt = $db->prepare("
        SELECT sub.subject_code, sub.subject_name
        FROM student_subjects ss
        JOIN subjects sub ON ss.subject_id = sub.id
        WHERE ss.enrollment_id = ?
        ORDER BY sub.subject_code
    ");
    $stmt->bind_param("i", $enrollment_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getEnrollmentPaymentSchedules($db, $enrollment_id) {
    $stmt = $db->prepare("SELECT * FROM payment_schedules WHERE enrollment_id = ? ORDER BY FIELD(payment_type, 'Prelim', 'Midterm', 'Pre-Final', 'Final')");
    $stmt->bind_param("i", $enrollment_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
