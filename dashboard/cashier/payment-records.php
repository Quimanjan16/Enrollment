<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('cashier');

$payments = $db->query("
    SELECT p.*, ps.payment_type, ps.amount_due, ps.payment_status,
           s.first_name, s.last_name, e.grade_level
    FROM payments p
    JOIN payment_schedules ps ON p.payment_schedule_id = ps.id
    JOIN enrollments e ON ps.enrollment_id = e.id
    JOIN students s ON e.student_id = s.id
    ORDER BY p.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$pending = $db->query("
    SELECT ps.*, s.first_name, s.last_name, e.grade_level
    FROM payment_schedules ps
    JOIN enrollments e ON ps.enrollment_id = e.id
    JOIN students s ON e.student_id = s.id
    WHERE ps.payment_status != 'paid'
    ORDER BY ps.due_date ASC
")->fetch_all(MYSQLI_ASSOC);

renderPageStart('Payment Records', 'cashier', 'payment-records', 'Cashier');
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Pending Payments</h3>
        <a href="<?php echo appUrl('/dashboard/cashier/receive-payment.php'); ?>" class="btn btn-success btn-sm">Receive Payment</a>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Grade</th>
                    <th>Type</th>
                    <th>Due</th>
                    <th>Paid</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($pending) > 0): foreach ($pending as $p): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></td>
                    <td><?php echo getGradeLevelName($p['grade_level']); ?></td>
                    <td><strong><?php echo htmlspecialchars($p['payment_type']); ?></strong></td>
                    <td><?php echo formatCurrency($p['amount_due']); ?></td>
                    <td><?php echo formatCurrency($p['amount_paid']); ?></td>
                    <td>
                        <span class="badge" style="background-color:<?php echo getPaymentStatusColor($p['payment_status']); ?>20;color:<?php echo getPaymentStatusColor($p['payment_status']); ?>;">
                            <?php echo ucfirst($p['payment_status']); ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?php echo appUrl('/dashboard/cashier/receive-payment.php?schedule_id=' . $p['id']); ?>" class="btn btn-success btn-sm">Receive</a>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="7" style="text-align:center;padding:32px;color:#6b7280;">No pending payments.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:24px;">
    <div class="card-header"><h3 class="card-title">Payment History</h3></div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Grade</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($payments) > 0): foreach ($payments as $p): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></td>
                    <td><?php echo getGradeLevelName($p['grade_level']); ?></td>
                    <td><?php echo htmlspecialchars($p['payment_type']); ?></td>
                    <td><?php echo formatCurrency($p['amount_paid']); ?></td>
                    <td><?php echo htmlspecialchars($p['payment_method']); ?></td>
                    <td><?php echo formatDateTime($p['created_at']); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="6" style="text-align:center;padding:32px;color:#6b7280;">No payment history yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderPageEnd(); ?>
