<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (!empty($_SESSION['user'])) {
    redirect('/Attachment/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    try {
        // Allow students to login using Admission Number (students.reg_no)
        // Admin/coordinator/assessor login using their username.
        $stmt = db()->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            $stmt = db()->prepare("
                SELECT u.*
                FROM users u
                JOIN students s ON s.id = u.student_id
                WHERE u.role = 'student' AND s.reg_no = ?
                LIMIT 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
        }
        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            $_SESSION['user'] = $user;
            redirect('/Attachment/dashboard.php');
        }
        $error = 'Invalid username or password.';
    } catch (PDOException) {
        $error = 'Database not ready. Please run install.php first.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - STVIAMS</title>
    <link rel="stylesheet" href="/Attachment/assets/style.css">
</head>
<body class="login-page">
<div class="login-box">
    <h1>STVIAMS</h1>
    <h2>Student Industrial Attachment Management System<br>Seme Technical and Vocational College</h2>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <label>Username / Admission Number</label>
        <input type="text" name="username" required autofocus>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit" class="btn" style="width:100%">Login</button>
    </form>
    <p style="margin-top:1rem;font-size:.8rem;color:#666;">First time? Open <a href="/Attachment/install.php">install.php</a></p>
</div>
</body>
</html>
