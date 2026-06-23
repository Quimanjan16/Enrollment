<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('admin');

$payments = $db->query("
    SELECT p.*, ps.payment_type, s.first_name, s.last_name, u.full_name as cashier_name,
           p.verification_status, v.full_name as verified_by_name
    FROM payments p
    JOIN payment_schedules ps ON p.payment_schedule_id = ps.id
    JOIN enrollments e ON ps.enrollment_id = e.id
    JOIN students s ON e.student_id = s.id
    JOIN users u ON p.paid_by = u.id
    LEFT JOIN users v ON p.verified_by = v.id
    ORDER BY p.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

renderPageStart('Payments', 'admin', 'payments', 'Administrator');
?>
<div class="card">
    <div class="card-header"><h3 class="card-title">Payment Records</h3></div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Payment Type</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Reference</th>
                    <th>Recorded By</th>
                    <th>Verified</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($payments) > 0): foreach ($payments as $p): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></td>
                    <td><strong><?php echo htmlspecialchars($p['payment_type']); ?></strong></td>
                    <td><?php echo formatCurrency($p['amount_paid']); ?></td>
                    <td><?php echo htmlspecialchars($p['payment_method']); ?></td>
                    <td><?php echo htmlspecialchars($p['reference_number'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($p['cashier_name']); ?></td>
                    <td>
                        <?php if (($p['verification_status'] ?? 'pending') === 'verified'): ?>
                        <span class="badge badge-success">Verified</span>
                        <?php else: ?>
                        <span class="badge badge-warning">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo formatDateTime($p['created_at']); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="8" style="text-align:center;padding:32px;color:#6b7280;">No payments recorded yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderPageEnd(); ?>
