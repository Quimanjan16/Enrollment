<?php
require_once __DIR__ . '/../../init.php';

$auth->requireRole('cashier');

$message = '';
$error = '';

// Get new enrollees
$query = "
SELECT s.*, 
       COUNT(d.id) as docs_count,
       GROUP_CONCAT(d.document_type) as doc_types
FROM students s
LEFT JOIN documents d ON s.id = d.student_id AND d.status = 'verified'
WHERE s.status = 'new'
GROUP BY s.id
ORDER BY s.created_at DESC
";

$new_enrollees = $db->query($query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Enrollees - School Enrollment System</title>
    <link rel="stylesheet" href="<?php echo appUrl('/public/styles.css'); ?>">
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
                        <span class="nav-icon">💳</span>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/cashier/new-enrollee.php'); ?>" class="nav-link active">
                        <span class="nav-icon">✨</span>
                        New Enrollees
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/cashier/students.php'); ?>" class="nav-link">
                        <span class="nav-icon">🎓</span>
                        Students
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/cashier/exam-eligibility.php'); ?>" class="nav-link">
                        <span class="nav-icon">✅</span>
                        Exam Eligibility
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/cashier/payment-records.php'); ?>" class="nav-link">
                        <span class="nav-icon">📄</span>
                        Payment Records
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
                    <h1 class="header-title">New Enrollees</h1>
                    <div class="user-menu">
                        <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                        <div>
                            <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong><br>
                            <small style="color: #6b7280;">Cashier</small>
                        </div>
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
                    <div class="card-header">
                        <h3 class="card-title">Students Ready for Enrollment</h3>
                    </div>

                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Date of Birth</th>
                                    <th>Contact</th>
                                    <th>Documents</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($new_enrollees) > 0): ?>
                                    <?php foreach ($new_enrollees as $enrollee): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($enrollee['first_name'] . ' ' . $enrollee['last_name']); ?></strong>
                                        </td>
                                        <td><?php echo formatDate($enrollee['date_of_birth']); ?></td>
                                        <td><?php echo htmlspecialchars($enrollee['contact_number'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge badge-success"><?php echo $enrollee['docs_count'] ?? 0; ?>/3</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-warning">Pending</span>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 8px;">
                                                <a href="<?php echo appUrl('/dashboard/cashier/view-student.php?id=' . $enrollee['id']); ?>" class="btn btn-secondary btn-sm">View</a>
                                                <a href="<?php echo appUrl('/dashboard/cashier/enroll-student.php?id=' . $enrollee['id']); ?>" class="btn btn-primary btn-sm">Enroll</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 32px; color: #6b7280;">
                                        No new enrollees at the moment.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
