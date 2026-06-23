<?php
require_once __DIR__ . '/../../init.php';

$auth->requireRole('admin');

$message = '';
$error = '';

$allowed_roles = ['cashier', 'assessment', 'registrar'];

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $username = cleanInput($_POST['username'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $full_name = cleanInput($_POST['full_name'] ?? '');
    $role = cleanInput($_POST['role'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($email) || empty($full_name) || empty($role) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!in_array($role, $allowed_roles, true)) {
        $error = 'Please select a valid role.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = $db->prepare($check_query);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Username or email already exists.';
        } else {
            $hashed_password = $auth->hashPassword($password);
            $insert_query = "INSERT INTO users (username, email, full_name, role, password, status)
                           VALUES (?, ?, ?, ?, ?, 'active')";
            $stmt = $db->prepare($insert_query);
            $stmt->bind_param("sssss", $username, $email, $full_name, $role, $hashed_password);

            if ($stmt->execute()) {
                $new_user_id = $stmt->insert_id;
                logActivity($db, $_SESSION['user_id'], 'Created user', 'user', $new_user_id,
                    "Created {$role} account: {$username}");
                $message = "User \"{$username}\" created successfully. They can now sign in with their own credentials.";
            } else {
                $error = 'Failed to create user. Please try again.';
            }
        }
    }
}

// Get all users
$users_query = "SELECT * FROM users ORDER BY created_at DESC";
$users = $db->query($users_query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - School Enrollment System</title>
    <link rel="stylesheet" href="<?php echo appUrl('/public/styles.css'); ?>">
    <style>
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-buttons a {
            font-size: 12px;
            padding: 6px 12px;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">📚</div>
            </div>

            <nav class="nav-menu">
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/index.php'); ?>" class="nav-link">
                        <span class="nav-icon">📊</span>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/admin/users.php'); ?>" class="nav-link active">
                        <span class="nav-icon">👥</span>
                        Manage Users
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/admin/students.php'); ?>" class="nav-link">
                        <span class="nav-icon">🎓</span>
                        Students
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/admin/enrollments.php'); ?>" class="nav-link">
                        <span class="nav-icon">📝</span>
                        Enrollments
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/admin/payments.php'); ?>" class="nav-link">
                        <span class="nav-icon">💳</span>
                        Payments
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/admin/scholarships.php'); ?>" class="nav-link">
                        <span class="nav-icon">🎖️</span>
                        Scholarships
                    </a>
                </li>
            </nav>

            <div class="sidebar-footer">
                <a href="<?php echo appUrl('/logout.php'); ?>" class="nav-link">
                    <span class="nav-icon">🚪</span>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-content">
                    <h1 class="header-title">Manage Users</h1>
                    <div class="header-actions">
                        <button class="btn btn-primary" onclick="openModal('userModal')">
                            ➕ Add New User
                        </button>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="content">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge badge-purple">
                                            <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo $user['status'] === 'active' ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo $user['status'] === 'active' ? '#065f46' : '#991b1b'; ?>;">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($user['created_at']); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="<?php echo appUrl('/dashboard/admin/edit-user.php?id=' . $user['id']); ?>" class="btn btn-secondary btn-sm">Edit</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal-overlay" id="userModalOverlay" onclick="closeModal('userModal')">
        <div class="modal" id="userModal" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h2 class="modal-title">Add New User</h2>
                <button class="modal-close" onclick="closeModal('userModal')">✕</button>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="">Select a role</option>
                        <option value="cashier">Cashier</option>
                        <option value="assessment">Assessment Personnel</option>
                        <option value="registrar">Registrar Personnel</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="card-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('userModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId + 'Overlay').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId + 'Overlay').classList.remove('active');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal('userModal');
            }
        });
    </script>
    <?php if ($error && ($_POST['action'] ?? '') === 'create'): ?>
    <script>document.addEventListener('DOMContentLoaded', function() { openModal('userModal'); });</script>
    <?php endif; ?>
</body>
</html>
