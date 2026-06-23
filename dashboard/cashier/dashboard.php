<?php
require_once __DIR__ . '/../../init.php';

$auth->requireRole('cashier');

// Get cashier statistics
$stats_query = "
SELECT 
    (SELECT COUNT(*) FROM students WHERE status = 'new') as new_enrollees,
    (SELECT COUNT(*) FROM payment_schedules WHERE payment_status != 'paid') as pending_payments,
    (SELECT SUM(amount_paid) FROM payments WHERE DATE(created_at) = CURDATE()) as today_collections,
    (SELECT COUNT(*) FROM enrollments WHERE enrollment_status = 'pending') as pending_enrollments
";

$result = $db->query($stats_query);
$stats = $result->fetch_assoc();

// Get pending new enrollees
$new_enrollees_query = "
SELECT s.*, 
       (SELECT COUNT(*) FROM documents WHERE student_id = s.id) as docs_count
FROM students s
WHERE s.status = 'new'
ORDER BY s.created_at DESC
LIMIT 10
";
$new_enrollees = $db->query($new_enrollees_query)->fetch_all(MYSQLI_ASSOC);

// Get pending payments
$pending_query = "
SELECT ps.*, e.student_id, s.first_name, s.last_name, e.grade_level
FROM payment_schedules ps
JOIN enrollments e ON ps.enrollment_id = e.id
JOIN students s ON e.student_id = s.id
WHERE ps.payment_status != 'paid'
ORDER BY ps.due_date ASC
LIMIT 10
";
$pending_payments = $db->query($pending_query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier Dashboard - School Enrollment System</title>
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
                    <a href="<?php echo appUrl('/dashboard/cashier/receive-payment.php'); ?>" class="nav-link">
                        <span class="nav-icon">💵</span>
                        Receive Payment
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/index.php'); ?>" class="nav-link active">
                        <span class="nav-icon">📊</span>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo appUrl('/dashboard/cashier/new-enrollee.php'); ?>" class="nav-link">
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
                    <h1 class="header-title">Cashier Dashboard</h1>
                    <div class="header-actions" style="display:flex;gap:12px;align-items:center;">
                        <a href="<?php echo appUrl('/dashboard/cashier/receive-payment.php'); ?>" class="btn btn-success">
                            💵 Receive Payment
                        </a>
                        <div class="user-menu">
                            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                            <div>
                                <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong><br>
                                <small style="color: #6b7280;">Cashier</small>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="content">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">New Enrollees</div>
                        <div class="stat-value"><?php echo $stats['new_enrollees'] ?? 0; ?></div>
                        <div class="stat-change positive">Waiting for enrollment</div>
                    </div>

                    <div class="stat-card" style="border-left-color: #f59e0b;">
                        <div class="stat-label">Pending Payments</div>
                        <div class="stat-value"><?php echo $stats['pending_payments'] ?? 0; ?></div>
                        <div class="stat-change">Requiring attention</div>
                    </div>

                    <div class="stat-card" style="border-left-color: #10b981;">
                        <div class="stat-label">Today's Collections</div>
                        <div class="stat-value"><?php echo formatCurrency($stats['today_collections'] ?? 0); ?></div>
                        <div class="stat-change positive">↑ Revenue today</div>
                    </div>

                    <div class="stat-card" style="border-left-color: #3b82f6;">
                        <div class="stat-label">Pending Enrollments</div>
                        <div class="stat-value"><?php echo $stats['pending_enrollments'] ?? 0; ?></div>
                        <div class="stat-change">To be processed</div>
                    </div>
                </div>

                <!-- New Enrollees -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">New Enrollees</h3>
                        <a href="<?php echo appUrl('/dashboard/cashier/new-enrollee.php'); ?>" class="btn btn-primary btn-sm">View All</a>
                    </div>

                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Documents</th>
                                    <th>Registered</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($new_enrollees as $enrollee): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($enrollee['first_name'] . ' ' . $enrollee['last_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($enrollee['contact_number'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge badge-info"><?php echo $enrollee['docs_count']; ?>/3</span>
                                    </td>
                                    <td><?php echo formatDate($enrollee['created_at']); ?></td>
                                    <td>
                                        <a href="<?php echo appUrl('/dashboard/cashier/enroll-student.php?student_id=' . $enrollee['id']); ?>" class="btn btn-primary btn-sm">Enroll</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pending Payments -->
                <div class="card" style="margin-top: 24px;">
                    <div class="card-header">
                        <h3 class="card-title">Pending Payments</h3>
                        <a href="<?php echo appUrl('/dashboard/cashier/receive-payment.php'); ?>" class="btn btn-success btn-sm">Receive Payment</a>
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
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($pending_payments) === 0): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center;padding:32px;color:#6b7280;">
                                        No pending payments. <a href="<?php echo appUrl('/dashboard/cashier/receive-payment.php'); ?>">Receive Payment</a> or enroll students first.
                                    </td>
                                </tr>
                                <?php else: foreach ($pending_payments as $payment): ?>
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
                                    <td>
                        <a href="<?php echo appUrl('/dashboard/cashier/receive-payment.php?schedule_id=' . $payment['id']); ?>" class="btn btn-success btn-sm">Record Payment</a>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
