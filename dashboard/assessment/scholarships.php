<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('assessment');

$scholarships = $db->query("
    SELECT ss.*, s.first_name, s.last_name, sh.scholarship_name, sh.scholarship_type, e.grade_level
    FROM student_scholarships ss
    JOIN students s ON ss.student_id = s.id
    JOIN scholarships sh ON ss.scholarship_id = sh.id
    JOIN enrollments e ON ss.enrollment_id = e.id
    ORDER BY FIELD(ss.status, 'pending', 'approved', 'active', 'cancelled'), ss.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

renderPageStart('Scholarships', 'assessment', 'scholarships', 'Assessment Personnel');
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Scholarship Applications</h3>
        <a href="<?php echo appUrl('/dashboard/assessment/apply-scholarship.php'); ?>" class="btn btn-primary btn-sm">Apply Scholarship</a>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Grade</th>
                    <th>Scholarship</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($scholarships) > 0): foreach ($scholarships as $sch): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($sch['first_name'] . ' ' . $sch['last_name']); ?></strong></td>
                    <td><?php echo getGradeLevelName($sch['grade_level']); ?></td>
                    <td><?php echo htmlspecialchars($sch['scholarship_name']); ?></td>
                    <td><?php echo formatCurrency($sch['approved_amount']); ?></td>
                    <td><span class="badge badge-warning"><?php echo ucfirst($sch['status']); ?></span></td>
                    <td><?php echo formatDate($sch['created_at']); ?></td>
                    <td>
                        <?php if ($sch['status'] === 'pending'): ?>
                        <a href="<?php echo appUrl('/dashboard/assessment/approve-scholarship.php?id=' . $sch['id']); ?>" class="btn btn-success btn-sm">Review</a>
                        <?php else: ?>
                        <span style="color:#6b7280;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="7" style="text-align:center;padding:32px;color:#6b7280;">No scholarship applications found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderPageEnd(); ?>
