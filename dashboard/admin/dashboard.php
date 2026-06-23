<?php
require_once __DIR__ . '/../../init.php';

$auth->requireRole('admin');

// Get dashboard statistics
$stats_query = "
SELECT 
    (SELECT COUNT(*) FROM students WHERE status IN ('enrolled', 'continuing')) as total_students,
    (SELECT COUNT(*) FROM enrollments WHERE enrollment_status = 'enrolled') as total_enrollments,
    (SELECT SUM(amount_paid) FROM payments) as total_payments,
    (SELECT COUNT(*) FROM users) as total_users
";

$result = $db->query($stats_query);
$stats = $result->fetch_assoc();

// Get recent enrollments
$recent_query = "
SELECT e.*, s.first_name, s.last_name, s.id as student_id
FROM enrollments e
JOIN students s ON e.student_id = s.id
ORDER BY e.created_at DESC
LIMIT 10
";
$recent_enrollments = $db->query($recent_query)->fetch_all(MYSQLI_ASSOC);

// Get recent payments
$payments_query = "
SELECT p.*, ps.payment_type, e.student_id, s.first_name, s.last_name
FROM payments p
JOIN payment_schedules ps ON p.payment_schedule_id = ps.id
JOIN enrollments e ON ps.enrollment_id = e.id
JOIN students s ON e.student_id = s.id
ORDER BY p.created_at DESC
LIMIT 10
";
$recent_payments = $db->query($payments_query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - School Enrollment System</title>
    <link rel="stylesheet" href="<?php echo appUrl('/public/styles.css'); ?>">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">📚</div>
                <div>
                    <div class="sidebar-logo" style="margin: 0; font-size: 14px;">SES</div>
                </div>
            </div>

            <nav class="nav-menu">
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/index.php'); ?>" class="nav-link active">
                        <span class="nav-icon">📊</span>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/admin/users.php'); ?>" class="nav-link">
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
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/admin/activity.php'); ?>" class="nav-link">
                        <span class="nav-icon">📋</span>
                        Activity Log
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
                    <h1 class="header-title">Admin Dashboard</h1>
                    <div class="user-menu">
                        <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                        <div>
                            <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong><br>
                            <small style="color: #6b7280;">Administrator</small>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="content">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total Students</div>
                        <div class="stat-value"><?php echo $stats['total_students'] ?? 0; ?></div>
                        <div class="stat-change positive">↑ Active enrollments</div>
                    </div>

                    <div class="stat-card" style="border-left-color: #3b82f6;">
                        <div class="stat-label">Total Enrollments</div>
                        <div class="stat-value"><?php echo $stats['total_enrollments'] ?? 0; ?></div>
                        <div class="stat-change">This semester</div>
                    </div>

                    <div class="stat-card" style="border-left-color: #10b981;">
                        <div class="stat-label">Total Payments</div>
                        <div class="stat-value"><?php echo formatCurrency($stats['total_payments'] ?? 0); ?></div>
                        <div class="stat-change positive">↑ Revenue collected</div>
                    </div>

                    <div class="stat-card" style="border-left-color: #f59e0b;">
                        <div class="stat-label">System Users</div>
                        <div class="stat-value"><?php echo $stats['total_users'] ?? 0; ?></div>
                        <div class="stat-change">Active accounts</div>
                    </div>
                </div>

                <!-- Recent Enrollments -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Enrollments</h3>
                        <a href="<?php echo appUrl('/dashboard/admin/enrollments.php'); ?>" class="btn btn-primary btn-sm">View All</a>
                    </div>

                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Grade</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_enrollments as $enrollment): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?></strong>
                                    </td>
                                    <td><?php echo getGradeLevelName($enrollment['grade_level']); ?></td>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo getEnrollmentStatusColor($enrollment['enrollment_status']); ?>20; color: <?php echo getEnrollmentStatusColor($enrollment['enrollment_status']); ?>;">
                                            <?php echo ucfirst($enrollment['enrollment_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatCurrency($enrollment['net_amount']); ?></td>
                                    <td><?php echo formatDate($enrollment['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Payments -->
                <div class="card" style="margin-top: 24px;">
                    <div class="card-header">
                        <h3 class="card-title">Recent Payments</h3>
                        <a href="<?php echo appUrl('/dashboard/admin/payments.php'); ?>" class="btn btn-primary btn-sm">View All</a>
                    </div>

                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Payment Type</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_payments as $payment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($payment['payment_type']); ?></strong></td>
                                    <td><?php echo formatCurrency($payment['amount_paid']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                    <td><?php echo formatDateTime($payment['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
