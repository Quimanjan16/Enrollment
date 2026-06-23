<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('admin');

$scholarships = $db->query("
    SELECT ss.*, s.first_name, s.last_name, sh.scholarship_name, sh.scholarship_type, u.full_name as approved_by_name
    FROM student_scholarships ss
    JOIN students s ON ss.student_id = s.id
    JOIN scholarships sh ON ss.scholarship_id = sh.id
    LEFT JOIN users u ON ss.approved_by = u.id
    ORDER BY ss.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

renderPageStart('Scholarships', 'admin', 'scholarships', 'Administrator');
?>
<div class="card">
    <div class="card-header"><h3 class="card-title">Scholarship Applications</h3></div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Scholarship</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Approved By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($scholarships) > 0): foreach ($scholarships as $sch): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($sch['first_name'] . ' ' . $sch['last_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($sch['scholarship_name']); ?></td>
                    <td><span class="badge badge-purple"><?php echo htmlspecialchars($sch['scholarship_type']); ?></span></td>
                    <td><?php echo formatCurrency($sch['approved_amount']); ?></td>
                    <td><span class="badge badge-warning"><?php echo ucfirst($sch['status']); ?></span></td>
                    <td><?php echo htmlspecialchars($sch['approved_by_name'] ?? '-'); ?></td>
                    <td><?php echo formatDate($sch['created_at']); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="7" style="text-align:center;padding:32px;color:#6b7280;">No scholarship records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderPageEnd(); ?>
