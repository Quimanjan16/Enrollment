<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('cashier');

$schedule_id = (int)($_GET['schedule_id'] ?? 0);
$message = '';
$error = '';

$query = "
    SELECT ps.*, e.student_id, e.grade_level, s.first_name, s.last_name
    FROM payment_schedules ps
    JOIN enrollments e ON ps.enrollment_id = e.id
    JOIN students s ON e.student_id = s.id
    WHERE ps.id = ?
";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$schedule = $stmt->get_result()->fetch_assoc();

if (!$schedule) {
    redirect('/dashboard/cashier/payment-records.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount_paid = (float)($_POST['amount_paid'] ?? 0);
    $payment_method = cleanInput($_POST['payment_method'] ?? '');
    $reference_number = cleanInput($_POST['reference_number'] ?? '');
    $notes = cleanInput($_POST['notes'] ?? '');

    if ($amount_paid <= 0 || empty($payment_method)) {
        $error = 'Amount and payment method are required.';
    } else {
        try {
            $db->begin_transaction();
            $result = recordStudentPayment($db, $schedule_id, $amount_paid, $payment_method, $reference_number, $notes, $_SESSION['user_id']);
            $db->commit();
            redirect('/dashboard/cashier/view-student.php?id=' . $schedule['student_id']);
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();
        }
    }
}

$balance = max(0, $schedule['amount_due'] - $schedule['amount_paid']);

renderPageStart('Record Payment', 'cashier', 'payment-records', 'Cashier');
renderAlerts($message, $error);
?>
<div class="card" style="max-width:640px;">
    <div class="card-header">
        <h3 class="card-title">Record Payment</h3>
        <a href="<?php echo appUrl('/dashboard/cashier/view-student.php?id=' . $schedule['student_id']); ?>" class="btn btn-secondary btn-sm">View Student Profile</a>
    </div>
    <div style="padding:0 24px 16px;">
        <p><strong>Student:</strong> <?php echo htmlspecialchars($schedule['first_name'] . ' ' . $schedule['last_name']); ?></p>
        <p><strong>Payment Type:</strong> <?php echo htmlspecialchars($schedule['payment_type']); ?></p>
        <p><strong>Balance:</strong> <?php echo formatCurrency($balance); ?></p>
    </div>
    <?php if ($schedule['payment_status'] !== 'paid'): ?>
    <form method="POST">
        <div class="form-group">
            <label for="amount_paid">Amount (₱)</label>
            <input type="number" id="amount_paid" name="amount_paid" min="0.01" max="<?php echo $balance; ?>" step="0.01" value="<?php echo number_format($balance, 2, '.', ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="payment_method">Payment Method</label>
            <select id="payment_method" name="payment_method" required>
                <option value="Cash">Cash</option>
                <option value="Check">Check</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Card">Card</option>
            </select>
        </div>
        <div class="form-group">
            <label for="reference_number">Reference Number</label>
            <input type="text" id="reference_number" name="reference_number">
        </div>
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3"></textarea>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-success">Record Payment</button>
        </div>
    </form>
    <?php else: ?>
    <div style="padding:24px;"><span class="badge badge-success">Fully Paid</span></div>
    <?php endif; ?>
</div>
<?php renderPageEnd(); ?>
