<?php
/**
 * Session Initialization
 * Include this file at the top of every page
 */

session_start();

// Set timezone
date_default_timezone_set('Asia/Manila');

// Include configuration files
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/config/Auth.php';
require_once __DIR__ . '/config/Helpers.php';
require_once __DIR__ . '/config/EnrollmentService.php';
require_once __DIR__ . '/includes/dashboard-nav.php';

// Initialize Database
$database = new Database();
$db = $database->connect();

ensureDatabaseSchema($db);

// Initialize Auth
$auth = new Auth($db);

// Get current user
$current_user = $auth->getUser();
