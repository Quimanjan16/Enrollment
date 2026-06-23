<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('registrar');

$students = $db->query("
    SELECT s.*,
           (SELECT COUNT(*) FROM documents WHERE student_id = s.id AND status = 'verified') as verified_docs,
           (SELECT COUNT(*) FROM documents WHERE student_id = s.id) as total_docs
    FROM students s
    ORDER BY s.last_name, s.first_name
")->fetch_all(MYSQLI_ASSOC);

renderPageStart('Students', 'registrar', 'students', 'Registrar Personnel');
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Student Profiles</h3>
        <a href="<?php echo appUrl('/dashboard/registrar/new-student.php'); ?>" class="btn btn-primary btn-sm">New Student</a>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Documents</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($students) > 0): foreach ($students as $s): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($s['last_name'] . ', ' . $s['first_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($s['contact_number'] ?? 'N/A'); ?></td>
                    <td><span class="badge badge-success"><?php echo (int)$s['verified_docs']; ?>/<?php echo (int)$s['total_docs']; ?></span></td>
                    <td><span class="badge badge-purple"><?php echo ucfirst($s['status']); ?></span></td>
                    <td>
                        <a href="<?php echo appUrl('/dashboard/registrar/edit-student.php?id=' . $s['id']); ?>" class="btn btn-primary btn-sm">Edit</a>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" style="text-align:center;padding:32px;color:#6b7280;">No students yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderPageEnd(); ?>
