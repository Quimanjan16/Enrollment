<?php
require_once __DIR__ . '/../../init.php';

$auth->requireRole('assessment');

// Get assessment statistics
$stats_query = "
SELECT 
    (SELECT COUNT(*) FROM student_scholarships WHERE status = 'pending') as pending_scholarships,
    (SELECT SUM(ss.approved_amount) FROM student_scholarships ss WHERE ss.status = 'active') as total_scholarships_active,
    (SELECT COUNT(*) FROM enrollments WHERE enrollment_status = 'enrolled') as total_enrolled,
    (SELECT COUNT(*) FROM payment_schedules WHERE payment_status = 'paid') as total_paid_fees
";

$result = $db->query($stats_query);
$stats = $result->fetch_assoc();

// Get pending scholarship approvals
$scholarship_query = "
SELECT ss.*, s.first_name, s.last_name, sh.scholarship_name, sh.scholarship_type, e.grade_level
FROM student_scholarships ss
JOIN students s ON ss.student_id = s.id
JOIN scholarships sh ON ss.scholarship_id = sh.id
JOIN enrollments e ON ss.enrollment_id = e.id
WHERE ss.status = 'pending'
ORDER BY ss.created_at DESC
LIMIT 10
";
$pending_scholarships = $db->query($scholarship_query)->fetch_all(MYSQLI_ASSOC);

// Get payment verification records
$payment_verify_query = "
SELECT ps.*, e.student_id, s.first_name, s.last_name, e.grade_level
FROM payment_schedules ps
JOIN enrollments e ON ps.enrollment_id = e.id
JOIN students s ON e.student_id = s.id
ORDER BY ps.updated_at DESC
LIMIT 10
";
$payment_records = $db->query($payment_verify_query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Dashboard - School Enrollment System</title>
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
                    <a href="<?php echo appUrl('/dashboard/index.php'); ?>" class="nav-link active">
                        <span class="nav-icon">✅</span>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/assessment/scholarships.php'); ?>" class="nav-link">
                        <span class="nav-icon">🎖️</span>
                        Scholarships
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/assessment/verify-payments.php'); ?>" class="nav-link">
                        <span class="nav-icon">💳</span>
                        Verify Payments
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/assessment/students.php'); ?>" class="nav-link">
                        <span class="nav-icon">🎓</span>
                        Students
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/assessment/verification-log.php'); ?>" class="nav-link">
                        <span class="nav-icon">📋</span>
                        Verification Log
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
                    <h1 class="header-title">Assessment Dashboard</h1>
                    <div class="user-menu">
                        <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                        <div>
                            <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong><br>
                            <small style="color: #6b7280;">Assessment Personnel</small>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="content">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Pending Scholarships</div>
                        <div class="stat-value"><?php echo $stats['pending_scholarships'] ?? 0; ?></div>
                        <div class="stat-change positive">Awaiting approval</div>
                    </div>

                    <div class="stat-card" style="border-left-color: #10b981;">
                        <div class="stat-label">Active Scholarships</div>
                        <div class="stat-value"><?php echo formatCurrency($stats['total_scholarships_active'] ?? 0); ?></div>
                        <div class="stat-change">Total deductions</div>
                    </div>

                    <div class="stat-card" style="border-left-color: #3b82f6;">
                        <div class="stat-label">Total Enrolled</div>
                        <div class="stat-value"><?php echo $stats['total_enrolled'] ?? 0; ?></div>
                        <div class="stat-change">Active students</div>
                    </div>

                    <div class="stat-card" style="border-left-color: #f59e0b;">
                        <div class="stat-label">Paid Fees</div>
                        <div class="stat-value"><?php echo $stats['total_paid_fees'] ?? 0; ?></div>
                        <div class="stat-change">Verified payments</div>
                    </div>
                </div>

                <!-- Pending Scholarships -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Pending Scholarship Approvals</h3>
                        <a href="<?php echo appUrl('/dashboard/assessment/scholarships.php'); ?>" class="btn btn-primary btn-sm">View All</a>
                    </div>

                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Grade</th>
                                    <th>Scholarship</th>
                                    <th>Amount</th>
                                    <th>Type</th>
                                    <th>Submitted</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_scholarships as $scholarship): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($scholarship['first_name'] . ' ' . $scholarship['last_name']); ?></strong>
                                    </td>
                                    <td><?php echo getGradeLevelName($scholarship['grade_level']); ?></td>
                                    <td><?php echo htmlspecialchars($scholarship['scholarship_name']); ?></td>
                                    <td><?php echo formatCurrency($scholarship['approved_amount']); ?></td>
                                    <td>
                                        <span class="badge badge-purple">
                                            <?php echo htmlspecialchars($scholarship['scholarship_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($scholarship['created_at']); ?></td>
                                    <td>
                                        <a href="<?php echo appUrl('/dashboard/assessment/approve-scholarship.php?id=' . $scholarship['id']); ?>" class="btn btn-success btn-sm">Review</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Payment Verification Records -->
                <div class="card" style="margin-top: 24px;">
                    <div class="card-header">
                        <h3 class="card-title">Payment Records</h3>
                        <a href="<?php echo appUrl('/dashboard/assessment/verify-payments.php'); ?>" class="btn btn-primary btn-sm">View All</a>
                    </div>

                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Grade</th>
                                    <th>Payment Type</th>
                                    <th>Amount Due</th>
                                    <th>Paid</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payment_records as $payment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
                                    <td><?php echo getGradeLevelName($payment['grade_level']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($payment['payment_type']); ?></strong></td>
                                    <td><?php echo formatCurrency($payment['amount_due']); ?></td>
                                    <td><?php echo formatCurrency($payment['amount_paid']); ?></td>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo getPaymentStatusColor($payment['payment_status']); ?>20; color: <?php echo getPaymentStatusColor($payment['payment_status']); ?>;">
                                            <?php echo ucfirst($payment['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDateTime($payment['updated_at']); ?></td>
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
