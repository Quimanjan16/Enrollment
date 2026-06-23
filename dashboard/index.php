<?php
require_once __DIR__ . '/../init.php';

$auth->requireLogin();

redirect(getDashboardPathForRole($_SESSION['role']));
