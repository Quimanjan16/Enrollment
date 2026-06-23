<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('assessment');

$scholarship_id = (int)($_GET['id'] ?? 0);
$message = '';
$error = '';

$query = "
    SELECT ss.*, s.first_name, s.last_name, sh.scholarship_name, sh.scholarship_type, e.grade_level, e.net_amount
    FROM student_scholarships ss
    JOIN students s ON ss.student_id = s.id
    JOIN scholarships sh ON ss.scholarship_id = sh.id
    JOIN enrollments e ON ss.enrollment_id = e.id
    WHERE ss.id = ?
";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $scholarship_id);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();

if (!$record) {
    redirect('/dashboard/assessment/scholarships.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = cleanInput($_POST['action'] ?? '');
    $notes = cleanInput($_POST['notes'] ?? '');

    if (!in_array($action, ['approve', 'reject'], true)) {
        $error = 'Please choose approve or reject.';
    } elseif ($record['status'] !== 'pending') {
        $error = 'This application has already been processed.';
    } else {
        try {
            $db->begin_transaction();
            approveScholarship($db, $scholarship_id, $action === 'approve', $_SESSION['user_id'], $notes);
            $db->commit();
            $message = $action === 'approve'
                ? 'Scholarship approved. Deduction split across remaining payments — visible to cashier and registrar.'
                : 'Scholarship application rejected.';
            $stmt->execute();
            $record = $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();
        }
    }
}

renderPageStart('Review Scholarship', 'assessment', 'scholarships', 'Assessment Personnel');
renderAlerts($message, $error);
?>
<div class="card" style="max-width:640px;">
    <div class="card-header">
        <h3 class="card-title">Scholarship Review</h3>
        <a href="<?php echo appUrl('/dashboard/assessment/scholarships.php'); ?>" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <div style="padding:24px;">
        <p><strong>Student:</strong> <?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></p>
        <p><strong>Grade:</strong> <?php echo getGradeLevelName($record['grade_level']); ?></p>
        <p><strong>Scholarship:</strong> <?php echo htmlspecialchars($record['scholarship_name']); ?></p>
        <p><strong>Type:</strong> <?php echo htmlspecialchars($record['scholarship_type']); ?></p>
        <p><strong>Deduction Amount:</strong> <?php echo formatCurrency($record['approved_amount']); ?></p>
        <p><strong>Current Net Tuition:</strong> <?php echo formatCurrency($record['net_amount']); ?></p>
        <p><strong>Status:</strong> <span class="badge badge-warning"><?php echo ucfirst($record['status']); ?></span></p>
    </div>
    <?php if ($record['status'] === 'pending'): ?>
    <form method="POST">
        <div class="form-group">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3"></textarea>
        </div>
        <div class="card-footer" style="display:flex;gap:8px;">
            <button type="submit" name="action" value="approve" class="btn btn-success">Approve &amp; Apply Deduction</button>
            <button type="submit" name="action" value="reject" class="btn btn-secondary">Reject</button>
        </div>
    </form>
    <?php endif; ?>
</div>
<?php renderPageEnd(); ?>
