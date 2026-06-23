<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('assessment');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = (int)($_POST['payment_id'] ?? 0);
    if ($payment_id <= 0) {
        $error = 'Invalid payment selected.';
    } else {
        try {
            verifyPayment($db, $payment_id, $_SESSION['user_id']);
            $message = 'Payment verified successfully. Cashier and admin records are updated.';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$payments = $db->query("
    SELECT p.*, ps.payment_type, ps.amount_due, ps.amount_paid, ps.payment_status,
           s.first_name, s.last_name, e.grade_level, e.academic_year, e.semester,
           e.additional_fees, e.scholarship_amount, e.net_amount,
           u.full_name as cashier_name, v.full_name as verified_by_name
    FROM payments p
    JOIN payment_schedules ps ON p.payment_schedule_id = ps.id
    JOIN enrollments e ON ps.enrollment_id = e.id
    JOIN students s ON e.student_id = s.id
    JOIN users u ON p.paid_by = u.id
    LEFT JOIN users v ON p.verified_by = v.id
    ORDER BY p.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

renderPageStart('Verify Payments', 'assessment', 'verify-payments', 'Assessment Personnel');
renderAlerts($message, $error);
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Payment Verification</h3>
        <p style="margin:0;color:#6b7280;font-size:14px;">Confirm that payments recorded by the cashier are correct, including amounts affected by additional fees and scholarships.</p>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Grade</th>
                    <th>Period</th>
                    <th>Amount Paid</th>
                    <th>Schedule Due</th>
                    <th>Add'l Fees</th>
                    <th>Scholarship</th>
                    <th>Cashier</th>
                    <th>Verification</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($payments) > 0): foreach ($payments as $p): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></strong></td>
                    <td><?php echo getGradeLevelName($p['grade_level']); ?></td>
                    <td><?php echo htmlspecialchars($p['payment_type']); ?></td>
                    <td><?php echo formatCurrency($p['amount_paid']); ?></td>
                    <td><?php echo formatCurrency($p['amount_due']); ?> (<?php echo ucfirst($p['payment_status']); ?>)</td>
                    <td><?php echo formatCurrency($p['additional_fees']); ?></td>
                    <td><?php echo formatCurrency($p['scholarship_amount']); ?></td>
                    <td><?php echo htmlspecialchars($p['cashier_name']); ?></td>
                    <td>
                        <?php if (($p['verification_status'] ?? 'pending') === 'verified'): ?>
                        <span class="badge badge-success">Verified</span><br>
                        <small><?php echo htmlspecialchars($p['verified_by_name'] ?? ''); ?></small>
                        <?php else: ?>
                        <span class="badge badge-warning">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo formatDateTime($p['created_at']); ?></td>
                    <td>
                        <?php if (($p['verification_status'] ?? 'pending') !== 'verified'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="payment_id" value="<?php echo $p['id']; ?>">
                            <button type="submit" class="btn btn-success btn-sm">Confirm Paid</button>
                        </form>
                        <?php else: ?>
                        <span style="color:#6b7280;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="11" style="text-align:center;padding:32px;color:#6b7280;">No payments to verify yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderPageEnd(); ?>
