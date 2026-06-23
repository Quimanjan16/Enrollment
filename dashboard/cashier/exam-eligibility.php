<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('cashier');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enrollment_id = (int)($_POST['enrollment_id'] ?? 0);
    $exam_period = cleanInput($_POST['exam_period'] ?? '');
    $is_eligible = isset($_POST['is_eligible']) ? 1 : 0;
    $periods = ['Prelim', 'Midterm', 'Pre-Final', 'Final'];

    if ($enrollment_id <= 0 || !in_array($exam_period, $periods, true)) {
        $error = 'Invalid exam eligibility data.';
    } else {
        $check = $db->prepare("SELECT id FROM exam_eligibility WHERE enrollment_id = ? AND exam_period = ?");
        $check->bind_param("is", $enrollment_id, $exam_period);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();

        if ($existing) {
            $stmt = $db->prepare("UPDATE exam_eligibility SET is_eligible = ?, checked_by = ?, checked_at = NOW() WHERE id = ?");
            $stmt->bind_param("iii", $is_eligible, $_SESSION['user_id'], $existing['id']);
        } else {
            $stmt = $db->prepare("INSERT INTO exam_eligibility (enrollment_id, exam_period, is_eligible, checked_by, checked_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("isii", $enrollment_id, $exam_period, $is_eligible, $_SESSION['user_id']);
        }

        if ($stmt->execute()) {
            logActivity($db, $_SESSION['user_id'], 'Exam Eligibility Updated', 'enrollment', $enrollment_id, "{$exam_period}: " . ($is_eligible ? 'Eligible' : 'Not eligible'));
            $message = 'Exam eligibility updated successfully.';
        } else {
            $error = 'Failed to update exam eligibility.';
        }
    }
}

$enrollments = $db->query("
    SELECT e.*, s.first_name, s.last_name,
           (SELECT payment_status FROM payment_schedules WHERE enrollment_id = e.id AND payment_type = 'Prelim' LIMIT 1) as prelim_status
    FROM enrollments e
    JOIN students s ON e.student_id = s.id
    WHERE e.enrollment_status IN ('enrolled', 'verified', 'pending')
    ORDER BY s.last_name, s.first_name
")->fetch_all(MYSQLI_ASSOC);

renderPageStart('Exam Eligibility', 'cashier', 'exam-eligibility', 'Cashier');
renderAlerts($message, $error);
?>
<div class="card">
    <div class="card-header"><h3 class="card-title">Mark Exam Eligibility</h3></div>
    <form method="POST" style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
        <div style="display: grid; grid-template-columns: 1fr 1fr auto auto; gap: 16px; align-items: end;">
            <div class="form-group" style="margin:0;">
                <label for="enrollment_id">Enrollment</label>
                <select id="enrollment_id" name="enrollment_id" required>
                    <option value="">Select student</option>
                    <?php foreach ($enrollments as $e): ?>
                    <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['last_name'] . ', ' . $e['first_name'] . ' - ' . getGradeLevelName($e['grade_level'])); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label for="exam_period">Exam Period</label>
                <select id="exam_period" name="exam_period" required>
                    <option value="Prelim">Prelim</option>
                    <option value="Midterm">Midterm</option>
                    <option value="Pre-Final">Pre-Final</option>
                    <option value="Final">Final</option>
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label><input type="checkbox" name="is_eligible" value="1"> Eligible</label>
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Grade</th>
                    <th>Prelim</th>
                    <th>Midterm</th>
                    <th>Pre-Final</th>
                    <th>Final</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($enrollments as $e):
                    $periods = ['Prelim', 'Midterm', 'Pre-Final', 'Final'];
                    $eligibility = [];
                    $stmt = $db->prepare("SELECT exam_period, is_eligible FROM exam_eligibility WHERE enrollment_id = ?");
                    $stmt->bind_param("i", $e['id']);
                    $stmt->execute();
                    foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $row) {
                        $eligibility[$row['exam_period']] = $row['is_eligible'];
                    }
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($e['first_name'] . ' ' . $e['last_name']); ?></strong></td>
                    <td><?php echo getGradeLevelName($e['grade_level']); ?></td>
                    <?php foreach ($periods as $p): ?>
                    <td>
                        <?php if (isset($eligibility[$p])): ?>
                        <span class="badge <?php echo $eligibility[$p] ? 'badge-success' : 'badge-warning'; ?>">
                            <?php echo $eligibility[$p] ? 'Eligible' : 'Not Eligible'; ?>
                        </span>
                        <?php else: ?>
                        <span class="badge" style="background:#f3f4f6;color:#6b7280;">Unset</span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php renderPageEnd(); ?>
