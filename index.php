<?php
require_once __DIR__ . '/init.php';

// If logged in, redirect to dashboard
if ($auth->isLoggedIn()) {
    redirect('/dashboard/index.php');
}

// Otherwise, redirect to login
redirect('/login.php');
