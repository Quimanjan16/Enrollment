<?php
require_once __DIR__ . '/init.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } elseif ($auth->login($username, $password)) {
        logActivity($db, $_SESSION['user_id'], 'User Login', 'user', $_SESSION['user_id']);
        redirect(getDashboardPathForRole($_SESSION['role']));
    } else {
        $error = 'Invalid username or password.';
    }
}

// If already logged in, redirect to dashboard
if ($auth->isLoggedIn()) {
    redirect(getDashboardPathForRole($_SESSION['role']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School Enrollment System</title>
    <link rel="stylesheet" href="<?php echo appUrl('/public/styles.css'); ?>">
    <style>
        .login-background {
            background: linear-gradient(135deg, #7c3aed 0%, #9b62fc 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
            padding: 48px;
            width: 100%;
            max-width: 420px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 48px;
        }

        .logo-circle {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #7c3aed 0%, #9b62fc 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            font-weight: 700;
            margin: 0 auto 16px;
        }

        .login-title {
            font-size: 28px;
            font-weight: 700;
            color: #1e1b2e;
            margin: 0 0 8px;
        }

        .login-subtitle {
            color: #6b7280;
            font-size: 14px;
            margin: 0;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #1e1b2e;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            font-size: 14px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background-color: white;
            color: #1e1b2e;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px #ede9fe;
        }

        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            border-left: 4px solid #ef4444;
            background-color: #fef2f2;
            color: #991b1b;
            font-size: 14px;
        }

        .submit-btn {
            width: 100%;
            padding: 12px 16px;
            background: linear-gradient(135deg, #7c3aed 0%, #9b62fc 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            box-shadow: 0 10px 20px rgba(124, 58, 237, 0.3);
            transform: translateY(-2px);
        }

        .login-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }

        .demo-credentials {
            background-color: #f5f3ff;
            border: 1px solid #e9d5ff;
            border-radius: 8px;
            padding: 12px;
            margin-top: 16px;
            font-size: 12px;
            color: #3b0764;
        }

        .demo-credentials strong {
            display: block;
            margin-bottom: 4px;
        }
    </style>
</head>
<body class="login-background">
    <div class="login-container">
        <div class="login-header">
            <div class="logo-circle">📚</div>
            <h1 class="login-title">Enrollment System</h1>
            <p class="login-subtitle">School Management Portal</p>
        </div>

        <?php if ($error): ?>
            <div class="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="submit-btn">Sign In</button>
        </form>

        <div class="demo-credentials">
            <strong>Demo Account:</strong>
            Username: admin<br>
            Password: admin123
        </div>

        <div class="login-footer">
            <p>&copy; 2024 School Enrollment System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
