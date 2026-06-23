<?php
/**
 * Helper Functions
 */

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function cleanInput($data) {
    return trim($data);
}

function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function formatDateTime($date) {
    return date('M d, Y h:i A', strtotime($date));
}

function getGradeLevelName($level) {
    $grades = [
        7 => 'Grade 7',
        8 => 'Grade 8',
        9 => 'Grade 9',
        10 => 'Grade 10'
    ];
    return $grades[$level] ?? 'Unknown';
}

function redirect($url) {
    header('Location: ' . appUrl($url));
    exit();
}

function appBasePath() {
    $project_folder = basename(dirname(__DIR__));
    $script_name = $_SERVER['SCRIPT_NAME'] ?? '';
    $base_path = '/' . $project_folder;

    return (strpos($script_name, $base_path . '/') === 0 || $script_name === $base_path) ? $base_path : '';
}

function appUrl($path) {
    if (preg_match('/^https?:\/\//', $path)) {
        return $path;
    }

    $base_path = appBasePath();
    $path = '/' . ltrim($path, '/');

    return $base_path . $path;
}

function getDashboardPathForRole($role) {
    $dashboards = [
        'admin' => '/dashboard/admin/dashboard.php',
        'cashier' => '/dashboard/cashier/dashboard.php',
        'assessment' => '/dashboard/assessment/dashboard.php',
        'registrar' => '/dashboard/registrar/dashboard.php'
    ];

    return $dashboards[$role] ?? '/login.php';
}

function logActivity($db, $user_id, $action, $entity_type = null, $entity_id = null, $description = null) {
    $query = "INSERT INTO activity_log (user_id, action, entity_type, entity_id, description) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->bind_param("issis", $user_id, $action, $entity_type, $entity_id, $description);
    $stmt->execute();
}

function getPaymentStatusColor($status) {
    $colors = [
        'paid' => '#10b981',
        'partial' => '#f59e0b',
        'pending' => '#ef4444'
    ];
    return $colors[$status] ?? '#6b7280';
}

function getEnrollmentStatusColor($status) {
    $colors = [
        'enrolled' => '#10b981',
        'verified' => '#3b82f6',
        'pending' => '#f59e0b',
        'cancelled' => '#ef4444'
    ];
    return $colors[$status] ?? '#6b7280';
}

function calculateSplitPayments($total_amount, $additional_fee) {
    // Assumes 4 payments: Prelim, Midterm, Pre-Final, Final
    $new_total = $total_amount + $additional_fee;
    $base_payment = $new_total / 4;
    
    return [
        'Prelim' => $base_payment,
        'Midterm' => $base_payment,
        'Pre-Final' => $base_payment,
        'Final' => $base_payment
    ];
}

function getExamEligibilityByPayment($db, $enrollment_id, $exam_period) {
    // Get the payment schedule for this exam period
    $query = "SELECT * FROM payment_schedules 
              WHERE enrollment_id = ? AND payment_type = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("is", $enrollment_id, $exam_period);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $schedule = $result->fetch_assoc();
        return $schedule['payment_status'] === 'paid';
    }
    return false;
}
