<?php
require_once __DIR__ . '/../../init.php';

$auth->requireRole('admin');

$user_id = (int)($_GET['id'] ?? 0);
$message = '';
$error = '';
$allowed_roles = ['admin', 'cashier', 'assessment', 'registrar'];

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    redirect('/dashboard/admin/users.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = cleanInput($_POST['email'] ?? '');
    $full_name = cleanInput($_POST['full_name'] ?? '');
    $role = cleanInput($_POST['role'] ?? '');
    $status = cleanInput($_POST['status'] ?? 'active');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($full_name) || empty($role)) {
        $error = 'Email, full name, and role are required.';
    } elseif (!in_array($role, $allowed_roles, true)) {
        $error = 'Invalid role selected.';
    } elseif (!in_array($status, ['active', 'inactive'], true)) {
        $error = 'Invalid status selected.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $check = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->bind_param("si", $email, $user_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'Email is already used by another account.';
        } elseif (!empty($password) && strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            if (!empty($password)) {
                $hashed = $auth->hashPassword($password);
                $update = $db->prepare("UPDATE users SET email = ?, full_name = ?, role = ?, status = ?, password = ? WHERE id = ?");
                $update->bind_param("sssssi", $email, $full_name, $role, $status, $hashed, $user_id);
            } else {
                $update = $db->prepare("UPDATE users SET email = ?, full_name = ?, role = ?, status = ? WHERE id = ?");
                $update->bind_param("ssssi", $email, $full_name, $role, $status, $user_id);
            }

            if ($update->execute()) {
                logActivity($db, $_SESSION['user_id'], 'Updated user', 'user', $user_id, "Updated account: {$user['username']}");
                $message = 'User updated successfully.';
                $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            } else {
                $error = 'Failed to update user.';
            }
        }
    }
}

renderPageStart('Edit User', 'admin', 'users', 'Administrator');
renderAlerts($message, $error);
?>
<div class="card" style="max-width: 600px;">
    <div class="card-header">
        <h3 class="card-title">Edit: <?php echo htmlspecialchars($user['username']); ?></h3>
        <a href="<?php echo appUrl('/dashboard/admin/users.php'); ?>" class="btn btn-secondary btn-sm">Back</a>
    </div>
    <form method="POST">
        <div class="form-group">
            <label>Username</label>
            <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="role">Role</label>
            <select id="role" name="role" required>
                <?php foreach ($allowed_roles as $r): ?>
                <option value="<?php echo $r; ?>" <?php echo $user['role'] === $r ? 'selected' : ''; ?>><?php echo ucfirst($r); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        <div class="form-group">
            <label for="password">New Password (leave blank to keep current)</label>
            <input type="password" id="password" name="password" minlength="6">
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>
<?php renderPageEnd(); ?>
