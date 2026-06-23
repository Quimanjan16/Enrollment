<?php

function getDashboardNavItems($role) {
    $navs = [
        'admin' => [
            ['href' => '/dashboard/index.php', 'icon' => '📊', 'label' => 'Dashboard', 'key' => 'dashboard'],
            ['href' => '/dashboard/admin/users.php', 'icon' => '👥', 'label' => 'Manage Users', 'key' => 'users'],
            ['href' => '/dashboard/admin/students.php', 'icon' => '🎓', 'label' => 'Students', 'key' => 'students'],
            ['href' => '/dashboard/admin/enrollments.php', 'icon' => '📝', 'label' => 'Enrollments', 'key' => 'enrollments'],
            ['href' => '/dashboard/admin/payments.php', 'icon' => '💳', 'label' => 'Payments', 'key' => 'payments'],
            ['href' => '/dashboard/admin/scholarships.php', 'icon' => '🎖️', 'label' => 'Scholarships', 'key' => 'scholarships'],
            ['href' => '/dashboard/admin/activity.php', 'icon' => '📋', 'label' => 'Activity Log', 'key' => 'activity'],
        ],
        'cashier' => [
            ['href' => '/dashboard/index.php', 'icon' => '📊', 'label' => 'Dashboard', 'key' => 'dashboard'],
            ['href' => '/dashboard/cashier/receive-payment.php', 'icon' => '💵', 'label' => 'Receive Payment', 'key' => 'receive-payment'],
            ['href' => '/dashboard/cashier/new-enrollee.php', 'icon' => '✨', 'label' => 'New Enrollees', 'key' => 'new-enrollee'],
            ['href' => '/dashboard/cashier/students.php', 'icon' => '🎓', 'label' => 'Students', 'key' => 'students'],
            ['href' => '/dashboard/cashier/exam-eligibility.php', 'icon' => '✅', 'label' => 'Exam Eligibility', 'key' => 'exam-eligibility'],
            ['href' => '/dashboard/cashier/payment-records.php', 'icon' => '📄', 'label' => 'Payment History', 'key' => 'payment-records'],
        ],
        'registrar' => [
            ['href' => '/dashboard/index.php', 'icon' => '📋', 'label' => 'Dashboard', 'key' => 'dashboard'],
            ['href' => '/dashboard/registrar/new-student.php', 'icon' => '➕', 'label' => 'New Student', 'key' => 'new-student'],
            ['href' => '/dashboard/registrar/students.php', 'icon' => '🎓', 'label' => 'Students', 'key' => 'students'],
            ['href' => '/dashboard/registrar/documents.php', 'icon' => '📄', 'label' => 'Documents', 'key' => 'documents'],
            ['href' => '/dashboard/registrar/additional-fees.php', 'icon' => '💰', 'label' => 'Additional Fees', 'key' => 'additional-fees'],
        ],
        'assessment' => [
            ['href' => '/dashboard/index.php', 'icon' => '✅', 'label' => 'Dashboard', 'key' => 'dashboard'],
            ['href' => '/dashboard/assessment/scholarships.php', 'icon' => '🎖️', 'label' => 'Scholarships', 'key' => 'scholarships'],
            ['href' => '/dashboard/assessment/apply-scholarship.php', 'icon' => '➕', 'label' => 'Apply Scholarship', 'key' => 'apply-scholarship'],
            ['href' => '/dashboard/assessment/verify-payments.php', 'icon' => '💳', 'label' => 'Verify Payments', 'key' => 'verify-payments'],
            ['href' => '/dashboard/assessment/students.php', 'icon' => '🎓', 'label' => 'Students', 'key' => 'students'],
            ['href' => '/dashboard/assessment/verification-log.php', 'icon' => '📋', 'label' => 'Verification Log', 'key' => 'verification-log'],
        ],
    ];

    return $navs[$role] ?? [];
}

function renderDashboardSidebar($role, $activePage = '') {
    $items = getDashboardNavItems($role);
    ?>
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">📚</div>
        </div>
        <nav class="nav-menu">
            <?php foreach ($items as $item): ?>
            <li class="nav-item">
                <a href="<?php echo appUrl($item['href']); ?>" class="nav-link<?php echo $activePage === $item['key'] ? ' active' : ''; ?>">
                    <span class="nav-icon"><?php echo $item['icon']; ?></span>
                    <?php echo htmlspecialchars($item['label']); ?>
                </a>
            </li>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <a href="<?php echo appUrl('/logout.php'); ?>" class="nav-link">
                <span class="nav-icon">🚪</span>
                Logout
            </a>
        </div>
    </aside>
    <?php
}

function renderDashboardHeader($title, $roleLabel) {
    ?>
    <header class="header">
        <div class="header-content">
            <h1 class="header-title"><?php echo htmlspecialchars($title); ?></h1>
            <div class="user-menu">
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                <div>
                    <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong><br>
                    <small style="color: #6b7280;"><?php echo htmlspecialchars($roleLabel); ?></small>
                </div>
            </div>
        </div>
    </header>
    <?php
}

function renderPageStart($title, $role, $activePage, $roleLabel) {
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - School Enrollment System</title>
    <link rel="stylesheet" href="<?php echo appUrl('/public/styles.css'); ?>">
</head>
<body>
    <div class="dashboard-layout">
        <?php renderDashboardSidebar($role, $activePage); ?>
        <div class="main-content">
            <?php renderDashboardHeader($title, $roleLabel); ?>
            <div class="content">
    <?php
}

function renderPageEnd() {
    ?>
            </div>
        </div>
    </div>
</body>
</html>
    <?php
}

function renderAlerts($message = '', $error = '') {
    if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif;
    if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif;
}
