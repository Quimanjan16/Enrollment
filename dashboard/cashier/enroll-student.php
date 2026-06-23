<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('cashier');

$student_id = (int)($_GET['id'] ?? $_GET['student_id'] ?? 0);
$message = '';
$error = '';

$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    redirect('/dashboard/cashier/new-enrollee.php');
}

$doc_progress = getDocumentProgress($db, $student_id);
$verified_docs = (int)$doc_progress['verified'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $academic_year = cleanInput($_POST['academic_year'] ?? '');
    $semester = (int)($_POST['semester'] ?? 0);
    $grade_level = (int)($_POST['grade_level'] ?? 0);
    $total_tuition = (float)($_POST['total_tuition'] ?? 0);
    $require_docs = isset($_POST['require_docs']);

    if (empty($academic_year) || $semester < 1 || $semester > 2 || $grade_level < 7 || $grade_level > 10 || $total_tuition <= 0) {
        $error = 'Please fill in all enrollment fields correctly.';
    } elseif ($require_docs && $verified_docs < 3) {
        $error = 'All 3 documents (Form 137, Form 138, Report Card) must be verified by the registrar before enrollment.';
    } else {
        try {
            $db->begin_transaction();
            $enrollment_id = createEnrollment($db, $student_id, $academic_year, $semester, $grade_level, $total_tuition, $_SESSION['user_id']);
            $subject_count = count(getEnrollmentSubjects($db, $enrollment_id));
            $db->commit();
            $message = "Student enrolled in " . getGradeLevelName($grade_level) . " with {$subject_count} subjects and 4 payment schedules (Prelim, Midterm, Pre-Final, Final).";
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();
        }
    }
}

$current_year = date('Y');
$default_year = $current_year . '-' . ($current_year + 1);
$preview_grade = (int)($_GET['grade'] ?? 7);

$subjects_preview = $db->prepare("SELECT subject_code, subject_name FROM subjects WHERE grade_level = ? AND status = 'active' ORDER BY subject_code");
$subjects_preview->bind_param("i", $preview_grade);
$subjects_preview->execute();
$preview_subjects = $subjects_preview->get_result()->fetch_all(MYSQLI_ASSOC);

renderPageStart('Enroll Student', 'cashier', 'new-enrollee', 'Cashier');
renderAlerts($message, $error);
?>
<div class="card" style="max-width: 720px;">
    <div class="card-header">
        <h3 class="card-title">Enroll: <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h3>
        <a href="<?php echo appUrl('/dashboard/cashier/view-student.php?id=' . $student_id); ?>" class="btn btn-secondary btn-sm">View Profile</a>
    </div>
    <div style="padding:0 24px 16px;">
        <p><strong>Documents verified:</strong> <?php echo $verified_docs; ?>/3
        <?php if ($verified_docs < 3): ?>
        <span class="badge badge-warning">Registrar must verify requirements first</span>
        <?php else: ?>
        <span class="badge badge-success">Ready for enrollment</span>
        <?php endif; ?>
        </p>
        <p style="color:#6b7280;font-size:14px;">After the student completes requirements at the registrar and hands over the guide slip, set their grade level below. Tuition will be split into 4 payments per semester.</p>
    </div>
    <form method="POST">
        <div class="form-group">
            <label for="academic_year">Academic Year</label>
            <input type="text" id="academic_year" name="academic_year" value="<?php echo htmlspecialchars($default_year); ?>" required>
        </div>
        <div class="form-group">
            <label for="semester">Semester</label>
            <select id="semester" name="semester" required>
                <option value="1">1st Semester</option>
                <option value="2">2nd Semester</option>
            </select>
        </div>
        <div class="form-group">
            <label for="grade_level">Grade Level (New &amp; Continuing Students)</label>
            <select id="grade_level" name="grade_level" required onchange="window.location='?id=<?php echo $student_id; ?>&grade='+this.value">
                <?php for ($g = 7; $g <= 10; $g++): ?>
                <option value="<?php echo $g; ?>" <?php echo ((int)($_POST['grade_level'] ?? $preview_grade) === $g) ? 'selected' : ''; ?>><?php echo getGradeLevelName($g); ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="total_tuition">Total Tuition per Semester (₱)</label>
            <input type="number" id="total_tuition" name="total_tuition" min="1" step="0.01" value="12000" required>
            <small style="color:#6b7280;">Split equally: Prelim, Midterm, Pre-Final, Final (₱3,000 each at ₱12,000)</small>
        </div>
        <div class="form-group">
            <label><input type="checkbox" name="require_docs" value="1" checked> Require 3/3 verified documents before enrolling</label>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Create Enrollment</button>
        </div>
    </form>
</div>

<div class="card" style="margin-top:24px;">
    <div class="card-header"><h3 class="card-title">Subjects for <?php echo getGradeLevelName((int)($_POST['grade_level'] ?? $preview_grade)); ?> (Philippine Curriculum)</h3></div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Code</th><th>Subject</th></tr></thead>
            <tbody>
                <?php foreach ($preview_subjects as $s): ?>
                <tr><td><strong><?php echo htmlspecialchars($s['subject_code']); ?></strong></td><td><?php echo htmlspecialchars($s['subject_name']); ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderPageEnd(); ?>
