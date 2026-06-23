<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('registrar');

$student_id = (int)($_GET['id'] ?? 0);
$message = '';
$error = '';

$stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    redirect('/dashboard/registrar/students.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = cleanInput($_POST['first_name'] ?? '');
    $last_name = cleanInput($_POST['last_name'] ?? '');
    $middle_name = cleanInput($_POST['middle_name'] ?? '');
    $date_of_birth = cleanInput($_POST['date_of_birth'] ?? '');
    $gender = cleanInput($_POST['gender'] ?? '');
    $contact_number = cleanInput($_POST['contact_number'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $address = cleanInput($_POST['address'] ?? '');
    $status = cleanInput($_POST['status'] ?? 'new');

    if (empty($first_name) || empty($last_name) || empty($date_of_birth) || empty($gender)) {
        $error = 'Required fields must be filled in.';
    } else {
        $upd = $db->prepare("UPDATE students SET first_name=?, last_name=?, middle_name=?, date_of_birth=?, gender=?, contact_number=?, email=?, address=?, status=? WHERE id=?");
        $upd->bind_param("sssssssssi", $first_name, $last_name, $middle_name, $date_of_birth, $gender, $contact_number, $email, $address, $status, $student_id);
        if ($upd->execute()) {
            logActivity($db, $_SESSION['user_id'], 'Student Updated', 'student', $student_id, "{$first_name} {$last_name}");
            $message = 'Student profile updated successfully.';
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
        } else {
            $error = 'Failed to update student profile.';
        }
    }
}

renderPageStart('Edit Student', 'registrar', 'students', 'Registrar Personnel');
renderAlerts($message, $error);
?>
<div class="card" style="max-width: 720px;">
    <div class="card-header">
        <h3 class="card-title">Edit Student Profile</h3>
        <a href="<?php echo appUrl('/dashboard/registrar/students.php'); ?>" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <form method="POST">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label for="middle_name">Middle Name</label>
            <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($student['middle_name'] ?? ''); ?>">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label for="date_of_birth">Date of Birth</label>
                <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($student['date_of_birth']); ?>" required>
            </div>
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                    <option value="Male" <?php echo $student['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo $student['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="contact_number">Contact Number</label>
            <input type="text" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($student['contact_number'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address" rows="2"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <?php foreach (['new', 'enrolled', 'continuing', 'graduated', 'dropped'] as $st): ?>
                <option value="<?php echo $st; ?>" <?php echo $student['status'] === $st ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>
<?php renderPageEnd(); ?>
