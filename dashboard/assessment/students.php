<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('assessment');

$students = $db->query("
    SELECT s.*,
           (SELECT COUNT(*) FROM enrollments WHERE student_id = s.id AND enrollment_status = 'enrolled') as active_enrollments,
           (SELECT SUM(ss.approved_amount) FROM student_scholarships ss WHERE ss.student_id = s.id AND ss.status = 'active') as scholarship_total
    FROM students s
    WHERE s.status IN ('enrolled', 'continuing')
    ORDER BY s.last_name, s.first_name
")->fetch_all(MYSQLI_ASSOC);

renderPageStart('Students', 'assessment', 'students', 'Assessment Personnel');
?>
<div class="card">
    <div class="card-header"><h3 class="card-title">Enrolled Students</h3></div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th>Active Enrollments</th>
                    <th>Active Scholarships</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($students) > 0): foreach ($students as $s): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($s['contact_number'] ?? 'N/A'); ?></td>
                    <td><?php echo (int)$s['active_enrollments']; ?></td>
                    <td><?php echo formatCurrency($s['scholarship_total'] ?? 0); ?></td>
                    <td><span class="badge badge-purple"><?php echo ucfirst($s['status']); ?></span></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" style="text-align:center;padding:32px;color:#6b7280;">No enrolled students found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderPageEnd(); ?>
