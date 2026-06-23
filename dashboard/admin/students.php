<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('admin');

$students = $db->query("SELECT * FROM students ORDER BY last_name, first_name")->fetch_all(MYSQLI_ASSOC);

renderPageStart('Students', 'admin', 'students', 'Administrator');
?>
<div class="card">
    <div class="card-header"><h3 class="card-title">All Students</h3></div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Gender</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Registered</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($students) > 0): foreach ($students as $s): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($s['last_name'] . ', ' . $s['first_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($s['gender']); ?></td>
                    <td><?php echo htmlspecialchars($s['contact_number'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($s['email'] ?? 'N/A'); ?></td>
                    <td><span class="badge badge-purple"><?php echo ucfirst($s['status']); ?></span></td>
                    <td><?php echo formatDate($s['created_at']); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="6" style="text-align:center;padding:32px;color:#6b7280;">No students found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderPageEnd(); ?>
