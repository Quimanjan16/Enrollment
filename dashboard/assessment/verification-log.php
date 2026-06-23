<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('assessment');

$logs = $db->query("
    SELECT al.*, u.full_name
    FROM activity_log al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE al.action LIKE '%Verified%' OR al.action LIKE '%Scholarship%' OR al.entity_type IN ('payment', 'payment_schedule', 'scholarship')
    ORDER BY al.created_at DESC
    LIMIT 100
")->fetch_all(MYSQLI_ASSOC);

renderPageStart('Verification Log', 'assessment', 'verification-log', 'Assessment Personnel');
?>
<div class="card">
    <div class="card-header"><h3 class="card-title">Verification History</h3></div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Description</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($logs) > 0): foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo htmlspecialchars($log['full_name'] ?? 'System'); ?></td>
                    <td><strong><?php echo htmlspecialchars($log['action']); ?></strong></td>
                    <td><?php echo htmlspecialchars(($log['entity_type'] ?? '-') . ($log['entity_id'] ? ' #' . $log['entity_id'] : '')); ?></td>
                    <td><?php echo htmlspecialchars($log['description'] ?? '-'); ?></td>
                    <td><?php echo formatDateTime($log['created_at']); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" style="text-align:center;padding:32px;color:#6b7280;">No verification activity yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderPageEnd(); ?>
