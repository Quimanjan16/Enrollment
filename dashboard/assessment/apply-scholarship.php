<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('assessment');

$message = '';
$error = '';

$programs = $db->query("SELECT * FROM scholarships WHERE status = 'active' ORDER BY scholarship_name")->fetch_all(MYSQLI_ASSOC);

$enrollments = $db->query("
    SELECT e.*, s.id as student_id, s.first_name, s.last_name
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    WHERE e.enrollment_status IN ('enrolled', 'verified', 'pending')
    ORDER BY s.last_name, s.first_name
")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = (int)($_POST['student_id'] ?? 0);
    $scholarship_id = (int)($_POST['scholarship_id'] ?? 0);
    $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);

    if ($student_id <= 0 || $scholarship_id <= 0 || $enrollment_id <= 0) {
        $error = 'Select student, enrollment, and scholarship program.';
    } else {
        try {
            $db->begin_transaction();
            $result = createScholarshipApplication($db, $student_id, $scholarship_id, $enrollment_id, $_SESSION['user_id']);
            $db->commit();
            $message = 'Scholarship application created for ' . formatCurrency($result['approved_amount']) . '. Review and approve it from the Scholarships page.';
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();
        }
    }
}

renderPageStart('Apply Scholarship', 'assessment', 'apply-scholarship', 'Assessment Personnel');
renderAlerts($message, $error);
?>
<div class="card" style="max-width:720px;">
    <div class="card-header"><h3 class="card-title">Add Scholarship Deduction</h3></div>
    <p style="padding:0 24px;color:#6b7280;font-size:14px;">Assign a scholarship program to an enrolled student. Upon approval, the deduction is split across remaining unpaid installments and reflects for cashier and registrar.</p>
    <form method="POST">
        <div class="form-group">
            <label for="enrollment_id">Student Enrollment</label>
            <select id="enrollment_id" name="enrollment_id" required onchange="syncStudentId()">
                <option value="">Select enrollment</option>
                <?php foreach ($enrollments as $e): ?>
                <option value="<?php echo $e['id']; ?>" data-student="<?php echo $e['student_id']; ?>">
                    <?php echo htmlspecialchars($e['last_name'] . ', ' . $e['first_name'] . ' — ' . getGradeLevelName($e['grade_level']) . ' (' . $e['academic_year'] . ' Sem ' . $e['semester'] . ') — Net: ' . formatCurrency($e['net_amount'])); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" id="student_id" name="student_id" value="">
        </div>
        <div class="form-group">
            <label for="scholarship_id">Scholarship Program</label>
            <select id="scholarship_id" name="scholarship_id" required>
                <option value="">Select program</option>
                <?php foreach ($programs as $p): ?>
                <option value="<?php echo $p['id']; ?>">
                    <?php echo htmlspecialchars($p['scholarship_name']); ?>
                    (<?php echo $p['discount_percentage'] ? $p['discount_percentage'] . '%' : formatCurrency($p['discount_amount']); ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Create Application</button>
            <a href="<?php echo appUrl('/dashboard/assessment/scholarships.php'); ?>" class="btn btn-secondary">View Pending</a>
        </div>
    </form>
</div>
<script>
function syncStudentId() {
    const sel = document.getElementById('enrollment_id');
    const opt = sel.options[sel.selectedIndex];
    document.getElementById('student_id').value = opt ? opt.getAttribute('data-student') : '';
}
</script>
<?php renderPageEnd(); ?>
