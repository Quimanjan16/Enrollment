<?php
/**
 * API Handler for Scholarship Approval
 * Approves scholarships and applies deductions to payment schedules
 */

require_once __DIR__ . '/../init.php';

$auth->requireLogin();

// Only assessment can approve scholarships
if ($_SESSION['role'] !== 'assessment') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

$scholarship_id = (int)($_POST['scholarship_id'] ?? 0);
$student_scholarship_id = (int)($_POST['student_scholarship_id'] ?? 0);
$approved = (isset($_POST['approved']) && $_POST['approved'] === 'true');
$notes = sanitize($_POST['notes'] ?? '');

// Validation
if ($scholarship_id <= 0 || $student_scholarship_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid scholarship data']);
    exit;
}

try {
    $db->begin_transaction();

    // Get student scholarship details
    $query = "SELECT ss.*, e.id as enrollment_id, s.id as scholarship_id, s.discount_percentage, s.discount_amount 
             FROM student_scholarships ss
             JOIN enrollments e ON ss.enrollment_id = e.id
             JOIN scholarships s ON ss.scholarship_id = s.id
             WHERE ss.id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $student_scholarship_id);
    $stmt->execute();
    $record = $stmt->get_result()->fetch_assoc();

    if (!$record) {
        throw new Exception('Student scholarship record not found');
    }

    if ($approved) {
        // Update status to active
        $status = 'active';
        $update_query = "UPDATE student_scholarships 
                        SET status = ?, approved_by = ?, approved_date = NOW()
                        WHERE id = ?";
        $stmt = $db->prepare($update_query);
        $stmt->bind_param("sii", $status, $_SESSION['user_id'], $student_scholarship_id);
        $stmt->execute();

        // Update enrollment with scholarship deduction
        $deduction = $record['approved_amount'];
        $update_enroll = "UPDATE enrollments 
                         SET scholarship_amount = scholarship_amount + ?, 
                             net_amount = net_amount - ?
                         WHERE id = ?";
        $stmt = $db->prepare($update_enroll);
        $stmt->bind_param("ddi", $deduction, $deduction, $record['enrollment_id']);
        $stmt->execute();

        // Update payment schedules to reflect the deduction
        $get_schedules = "SELECT * FROM payment_schedules WHERE enrollment_id = ? ORDER BY id ASC";
        $stmt = $db->prepare($get_schedules);
        $stmt->bind_param("i", $record['enrollment_id']);
        $stmt->execute();
        $schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Split deduction across all unpaid schedules
        $unpaid_count = 0;
        foreach ($schedules as $schedule) {
            if ($schedule['payment_status'] !== 'paid') {
                $unpaid_count++;
            }
        }

        if ($unpaid_count > 0) {
            $split_deduction = $deduction / $unpaid_count;
            foreach ($schedules as $schedule) {
                if ($schedule['payment_status'] !== 'paid') {
                    $new_due = max(0, $schedule['amount_due'] - $split_deduction);
                    $update_schedule = "UPDATE payment_schedules SET amount_due = ? WHERE id = ?";
                    $stmt = $db->prepare($update_schedule);
                    $stmt->bind_param("di", $new_due, $schedule['id']);
                    $stmt->execute();
                }
            }
        }

        $action = "Scholarship approved for {$record['approved_amount']}";
    } else {
        // Reject scholarship
        $status = 'cancelled';
        $update_query = "UPDATE student_scholarships 
                        SET status = ?, approved_by = ?, approved_date = NOW()
                        WHERE id = ?";
        $stmt = $db->prepare($update_query);
        $stmt->bind_param("sii", $status, $_SESSION['user_id'], $student_scholarship_id);
        $stmt->execute();

        $action = "Scholarship rejected";
    }

    // Log activity
    logActivity($db, $_SESSION['user_id'], $action, 'scholarship', $student_scholarship_id, $notes);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => $action . ' successfully'
    ]);

} catch (Exception $e) {
    $db->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error processing scholarship: ' . $e->getMessage()
    ]);
}
