<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('registrar');

$message = '';
$error = '';
$grade_filter = (int)($_GET['grade'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = cleanInput($_POST['mode'] ?? 'single');
    $fee_description = cleanInput($_POST['fee_description'] ?? '');
    $fee_amount = (float)($_POST['fee_amount'] ?? 0);

    if (empty($fee_description) || $fee_amount <= 0) {
        $error = 'Description and amount are required.';
    } else {
        try {
            $db->begin_transaction();

            if ($mode === 'grade') {
                $grade_level = (int)($_POST['grade_level'] ?? 0);
                if ($grade_level < 7 || $grade_level > 10) {
                    throw new Exception('Select a valid grade level.');
                }
                $count = applyAdditionalFeeByGrade($db, $grade_level, $fee_description, $fee_amount, $_SESSION['user_id']);
                if ($count === 0) {
                    throw new Exception('No active enrollments found for ' . getGradeLevelName($grade_level) . '.');
                }
                $message = "Fee applied to {$count} " . getGradeLevelName($grade_level) . " enrollment(s). Amount split across remaining unpaid installments.";
            } else {
                $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);
                if ($enrollment_id <= 0) {
                    throw new Exception('Select an enrollment.');
                }
                $stmt = $db->prepare("SELECT grade_level FROM enrollments WHERE id = ?");
                $stmt->bind_param("i", $enrollment_id);
                $stmt->execute();
                $enrollment = $stmt->get_result()->fetch_assoc();
                addAdditionalFee($db, $enrollment_id, $fee_description, $fee_amount, $enrollment['grade_level'], $_SESSION['user_id']);
                $message = 'Additional fee added and split to remaining unpaid payments. Visible to cashier and assessment.';
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();
        }
    }
}

$enroll_query = "
    SELECT e.*, s.first_name, s.last_name
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    WHERE e.enrollment_status IN ('pending', 'verified', 'enrolled')
";
if ($grade_filter >= 7 && $grade_filter <= 10) {
    $enroll_query .= " AND e.grade_level = {$grade_filter}";
}
$enroll_query .= " ORDER BY e.grade_level, s.last_name";
$enrollments = $db->query($enroll_query)->fetch_all(MYSQLI_ASSOC);

$fees = $db->query("
    SELECT af.*, s.first_name, s.last_name, e.grade_level, u.full_name as created_by_name
    FROM additional_fees af
    JOIN enrollments e ON af.enrollment_id = e.id
    JOIN students s ON e.student_id = s.id
    LEFT JOIN users u ON af.created_by = u.id
    ORDER BY af.created_at DESC LIMIT 25
")->fetch_all(MYSQLI_ASSOC);

renderPageStart('Additional Fees', 'registrar', 'additional-fees', 'Registrar Personnel');
renderAlerts($message, $error);
?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Filter Enrollments by Grade</h3>
    </div>
    <div style="padding:16px 24px;display:flex;gap:8px;flex-wrap:wrap;">
        <a href="?" class="btn btn-secondary btn-sm <?php echo $grade_filter === 0 ? 'active' : ''; ?>">All Grades</a>
        <?php for ($g = 7; $g <= 10; $g++): ?>
        <a href="?grade=<?php echo $g; ?>" class="btn btn-secondary btn-sm <?php echo $grade_filter === $g ? 'active' : ''; ?>"><?php echo getGradeLevelName($g); ?></a>
        <?php endfor; ?>
    </div>
</div>

<div class="card" style="margin-top:24px;">
    <div class="card-header"><h3 class="card-title">Apply to Single Student</h3></div>
    <form method="POST">
        <input type="hidden" name="mode" value="single">
        <div class="form-group">
            <label for="enrollment_id">Enrollment</label>
            <select id="enrollment_id" name="enrollment_id">
                <option value="">Select enrollment</option>
                <?php foreach ($enrollments as $e): ?>
                <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars(getGradeLevelName($e['grade_level']) . ' — ' . $e['last_name'] . ', ' . $e['first_name'] . ' (' . $e['academic_year'] . ' Sem ' . $e['semester'] . ')'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="fee_description_single">Fee Description</label>
            <input type="text" id="fee_description_single" name="fee_description" placeholder="e.g. Field Trip Fee">
        </div>
        <div class="form-group">
            <label for="fee_amount_single">Amount (₱)</label>
            <input type="number" id="fee_amount_single" name="fee_amount" min="0.01" step="0.01">
        </div>
        <div class="card-footer"><button type="submit" class="btn btn-primary">Apply to Student</button></div>
    </form>
</div>

<div class="card" style="margin-top:24px;border-left:4px solid #7c3aed;">
    <div class="card-header"><h3 class="card-title">Apply to All Students in a Grade</h3></div>
    <p style="padding:0 24px;color:#6b7280;font-size:14px;">Example: Add ₱500 lab fee for all Grade 8 students. The system splits the fee across each student's remaining unpaid installments.</p>
    <form method="POST">
        <input type="hidden" name="mode" value="grade">
        <div class="form-group">
            <label for="grade_level">Grade Level</label>
            <select id="grade_level" name="grade_level" required>
                <?php for ($g = 7; $g <= 10; $g++): ?>
                <option value="<?php echo $g; ?>" <?php echo $grade_filter === $g ? 'selected' : ''; ?>><?php echo getGradeLevelName($g); ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="fee_description_grade">Fee Description</label>
            <input type="text" id="fee_description_grade" name="fee_description" placeholder="e.g. Science Lab Fee" required>
        </div>
        <div class="form-group">
            <label for="fee_amount_grade">Amount per Student (₱)</label>
            <input type="number" id="fee_amount_grade" name="fee_amount" min="0.01" step="0.01" required>
        </div>
        <div class="card-footer"><button type="submit" class="btn btn-primary">Apply to Grade</button></div>
    </form>
</div>

<div class="card" style="margin-top:24px;">
    <div class="card-header"><h3 class="card-title">Recent Additional Fees</h3></div>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Student</th><th>Grade</th><th>Description</th><th>Amount</th><th>Added By</th><th>Date</th></tr></thead>
            <tbody>
                <?php if (count($fees) > 0): foreach ($fees as $f): ?>
                <tr>
                    <td><?php echo htmlspecialchars($f['first_name'] . ' ' . $f['last_name']); ?></td>
                    <td><?php echo getGradeLevelName($f['grade_level']); ?></td>
                    <td><?php echo htmlspecialchars($f['fee_description']); ?></td>
                    <td><?php echo formatCurrency($f['fee_amount']); ?></td>
                    <td><?php echo htmlspecialchars($f['created_by_name'] ?? '-'); ?></td>
                    <td><?php echo formatDate($f['created_at']); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="6" style="text-align:center;padding:32px;color:#6b7280;">No additional fees yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderPageEnd(); ?>
