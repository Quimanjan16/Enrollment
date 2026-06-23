<?php
require_once __DIR__ . '/init.php';

$auth->logout();
redirect('/login.php');
