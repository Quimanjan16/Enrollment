<?php
require_once __DIR__ . '/../init.php';

$auth->requireLogin();

if ($_SESSION['role'] !== 'cashier') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

$schedule_id = (int)($_POST['schedule_id'] ?? 0);
$amount_paid = (float)($_POST['amount_paid'] ?? 0);
$payment_method = cleanInput($_POST['payment_method'] ?? '');
$reference_number = cleanInput($_POST['reference_number'] ?? '');
$notes = cleanInput($_POST['notes'] ?? '');

if ($schedule_id <= 0 || $amount_paid <= 0 || empty($payment_method)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

try {
    $db->begin_transaction();
    $result = recordStudentPayment($db, $schedule_id, $amount_paid, $payment_method, $reference_number, $notes, $_SESSION['user_id']);
    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Payment recorded successfully',
        'payment_id' => $result['payment_id'],
        'new_status' => $result['new_status'],
        'balance' => $result['balance']
    ]);
} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
