<?php
require_once __DIR__ . '/../../init.php';
$auth->requireRole('registrar');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = cleanInput($_POST['first_name'] ?? '');
    $last_name = cleanInput($_POST['last_name'] ?? '');
    $middle_name = cleanInput($_POST['middle_name'] ?? '');
    $date_of_birth = cleanInput($_POST['date_of_birth'] ?? '');
    $gender = cleanInput($_POST['gender'] ?? '');
    $contact_number = cleanInput($_POST['contact_number'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $address = cleanInput($_POST['address'] ?? '');

    if (empty($first_name) || empty($last_name) || empty($date_of_birth) || empty($gender)) {
        $error = 'First name, last name, date of birth, and gender are required.';
    } elseif (!in_array($gender, ['Male', 'Female'], true)) {
        $error = 'Invalid gender selected.';
    } else {
        $stmt = $db->prepare("INSERT INTO students (first_name, last_name, middle_name, date_of_birth, gender, contact_number, email, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new')");
        $stmt->bind_param("ssssssss", $first_name, $last_name, $middle_name, $date_of_birth, $gender, $contact_number, $email, $address);

        if ($stmt->execute()) {
            $student_id = $stmt->insert_id;
            $doc_types = ['Form 137', 'Form 138', 'Report Card'];
            foreach ($doc_types as $doc_type) {
                $doc = $db->prepare("INSERT INTO documents (student_id, document_type, status) VALUES (?, ?, 'pending')");
                $doc->bind_param("is", $student_id, $doc_type);
                $doc->execute();
            }
            logActivity($db, $_SESSION['user_id'], 'Student Created', 'student', $student_id, "{$first_name} {$last_name}");
            $message = 'Student profile created successfully with pending document records.';
        } else {
            $error = 'Failed to create student profile.';
        }
    }
}

renderPageStart('New Student', 'registrar', 'new-student', 'Registrar Personnel');
renderAlerts($message, $error);
?>
<div class="card" style="max-width: 720px;">
    <div class="card-header"><h3 class="card-title">Create Student Profile</h3></div>
    <form method="POST">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>
        </div>
        <div class="form-group">
            <label for="middle_name">Middle Name</label>
            <input type="text" id="middle_name" name="middle_name">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
            <div class="form-group">
                <label for="date_of_birth">Date of Birth</label>
                <input type="date" id="date_of_birth" name="date_of_birth" required>
            </div>
            <div class="form-group">
                <label for="gender">Gender</label>
                <select id="gender" name="gender" required>
                    <option value="">Select</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="contact_number">Contact Number</label>
            <input type="text" id="contact_number" name="contact_number">
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email">
        </div>
        <div class="form-group">
            <label for="address">Address</label>
            <textarea id="address" name="address" rows="2"></textarea>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Create Profile</button>
        </div>
    </form>
</div>
<?php renderPageEnd(); ?>
