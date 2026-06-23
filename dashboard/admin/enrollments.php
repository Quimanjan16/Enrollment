<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('admin');

$enrollments = $db->query("
    SELECT e.*, s.first_name, s.last_name
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    ORDER BY e.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

renderPageStart('Enrollments', 'admin', 'enrollments', 'Administrator');
?>
<div class="card">
    <div class="card-header"><h3 class="card-title">All Enrollments</h3></div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Grade</th>
                    <th>Year / Sem</th>
                    <th>Status</th>
                    <th>Net Amount</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($enrollments) > 0): foreach ($enrollments as $e): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?></strong></td>
                    <td><?php echo getGradeLevelName($e['grade_level']); ?></td>
                    <td><?php echo htmlspecialchars($e['academic_year'] . ' / Sem ' . $e['semester']); ?></td>
                    <td>
                        <span class="badge" style="background-color:<?php echo getEnrollmentStatusColor($e['enrollment_status']); ?>20;color:<?php echo getEnrollmentStatusColor($e['enrollment_status']); ?>;">
                            <?php echo ucfirst($e['enrollment_status']); ?>
                        </span>
                    </td>
                    <td><?php echo formatCurrency($e['net_amount']); ?></td>
                    <td><?php echo formatDate($e['created_at']); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="6" style="text-align:center;padding:32px;color:#6b7280;">No enrollments found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderPageEnd(); ?>
