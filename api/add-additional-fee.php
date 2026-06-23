<?php
/**
 * API Handler for Adding Additional Fees
 * Adds fees and automatically redistributes to remaining payment schedules
 */

require_once __DIR__ . '/../init.php';

$auth->requireLogin();

// Only registrar can add additional fees
if ($_SESSION['role'] !== 'registrar') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

$enrollment_id = (int)($_POST['enrollment_id'] ?? 0);
$fee_description = sanitize($_POST['fee_description'] ?? '');
$fee_amount = (float)($_POST['fee_amount'] ?? 0);
$applicable_grade = (int)($_POST['applicable_grade'] ?? null);

// Validation
if ($enrollment_id <= 0 || empty($fee_description) || $fee_amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid fee data']);
    exit;
}

try {
    $db->begin_transaction();

    // Get enrollment details
    $enroll_query = "SELECT * FROM enrollments WHERE id = ?";
    $stmt = $db->prepare($enroll_query);
    $stmt->bind_param("i", $enrollment_id);
    $stmt->execute();
    $enrollment = $stmt->get_result()->fetch_assoc();

    if (!$enrollment) {
        throw new Exception('Enrollment not found');
    }

    // Add additional fee record
    $fee_query = "INSERT INTO additional_fees 
                 (enrollment_id, fee_description, fee_amount, applicable_grade, created_by) 
                 VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($fee_query);
    $stmt->bind_param("isdii", $enrollment_id, $fee_description, $fee_amount, $applicable_grade, $_SESSION['user_id']);
    $stmt->execute();

    // Update enrollment net amount
    $new_net = $enrollment['net_amount'] + $fee_amount;
    $update_enroll = "UPDATE enrollments SET additional_fees = additional_fees + ?, net_amount = ? WHERE id = ?";
    $stmt = $db->prepare($update_enroll);
    $stmt->bind_param("ddi", $fee_amount, $new_net, $enrollment_id);
    $stmt->execute();

    // Get unpaid payment schedules
    $paid_query = "SELECT * FROM payment_schedules WHERE enrollment_id = ? AND payment_status != 'paid' ORDER BY id ASC";
    $stmt = $db->prepare($paid_query);
    $stmt->bind_param("i", $enrollment_id);
    $stmt->execute();
    $unpaid_schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Redistribute the additional fee among unpaid schedules
    if (count($unpaid_schedules) > 0) {
        $split_amount = $fee_amount / count($unpaid_schedules);

        foreach ($unpaid_schedules as $schedule) {
            $new_due = $schedule['amount_due'] + $split_amount;
            $update_schedule = "UPDATE payment_schedules SET amount_due = ? WHERE id = ?";
            $stmt = $db->prepare($update_schedule);
            $stmt->bind_param("di", $new_due, $schedule['id']);
            $stmt->execute();
        }
    }

    // Log activity
    logActivity($db, $_SESSION['user_id'], 'Additional Fee Added', 'enrollment', $enrollment_id,
                "Description: {$fee_description}, Amount: ₱{$fee_amount}");

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Additional fee added and distributed successfully',
        'new_net_amount' => $new_net
    ]);

} catch (Exception $e) {
    $db->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error adding fee: ' . $e->getMessage()
    ]);
}
