<?php
require_once __DIR__ . '/../init.php';

$auth->requireLogin();

if (!in_array($_SESSION['role'], ['cashier', 'registrar'], true)) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

$student_id = (int)($_POST['student_id'] ?? 0);
$academic_year = cleanInput($_POST['academic_year'] ?? '');
$semester = (int)($_POST['semester'] ?? 0);
$grade_level = (int)($_POST['grade_level'] ?? 0);
$total_tuition = (float)($_POST['total_tuition'] ?? 0);

if ($student_id <= 0 || empty($academic_year) || $semester < 1 || $semester > 2 || $grade_level < 7 || $grade_level > 10 || $total_tuition <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid enrollment data']);
    exit;
}

try {
    $db->begin_transaction();
    $enrollment_id = createEnrollment($db, $student_id, $academic_year, $semester, $grade_level, $total_tuition, $_SESSION['user_id']);
    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Enrollment created with subjects and payment schedule',
        'enrollment_id' => $enrollment_id
    ]);
} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
