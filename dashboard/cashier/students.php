<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('cashier');

$students = $db->query("
    SELECT s.*, 
           (SELECT COUNT(*) FROM enrollments WHERE student_id = s.id) as enrollment_count
    FROM students s
    WHERE s.status IN ('enrolled', 'continuing', 'new')
    ORDER BY s.last_name, s.first_name
")->fetch_all(MYSQLI_ASSOC);

renderPageStart('Students', 'cashier', 'students', 'Cashier');
?>
<div class="card">
    <div class="card-header"><h3 class="card-title">Student Records</h3></div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Enrollments</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($students) > 0): foreach ($students as $s): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($s['contact_number'] ?? 'N/A'); ?></td>
                    <td><span class="badge badge-purple"><?php echo ucfirst($s['status']); ?></span></td>
                    <td><?php echo (int)$s['enrollment_count']; ?></td>
                    <td>
                        <a href="<?php echo appUrl('/dashboard/cashier/view-student.php?id=' . $s['id']); ?>" class="btn btn-secondary btn-sm">View</a>
                        <?php if ((int)$s['enrollment_count'] > 0): ?>
                        <a href="<?php echo appUrl('/dashboard/cashier/receive-payment.php'); ?>" class="btn btn-success btn-sm">Receive Payment</a>
                        <?php endif; ?>
                        <?php if ($s['status'] === 'new'): ?>
                        <a href="<?php echo appUrl('/dashboard/cashier/enroll-student.php?id=' . $s['id']); ?>" class="btn btn-primary btn-sm">Enroll</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" style="text-align:center;padding:32px;color:#6b7280;">No students found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderPageEnd(); ?>
