<?php
/**
 * One-time installer. Run once: http://localhost/Attachment/install.php
 * Then delete or rename this file for security.
 */
$host = 'localhost';
$user = 'root';
$pass = '';
$dbName = 'stviams';

$messages = [];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $sql = file_get_contents(__DIR__ . '/database/schema.sql');
        $pdo->exec($sql);
        $messages[] = 'Installation complete (tables created/verified).';

        foreach (['uploads/logbooks', 'uploads/recommendations'] as $dir) {
            $path = __DIR__ . '/' . $dir;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
        }
        $messages[] = 'Upload folders ready.';
        $messages[] = 'Login: admin / password (also coordinator, assessor1)';
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Install STVIAMS</title>
    <link rel="stylesheet" href="/Attachment/assets/style.css">
</head>
<body class="login-page">
<div class="login-box">
    <h1>STVIAMS Installer</h1>
    <h2>Seme Technical and Vocational College</h2>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php foreach ($messages as $m): ?>
        <div class="alert alert-success"><?= htmlspecialchars($m) ?></div>
    <?php endforeach; ?>
    <?php if (!$messages): ?>
        <p style="margin-bottom:1rem;font-size:.9rem;">This will create database <strong>stviams</strong> and default users. Ensure MySQL is running in XAMPP.</p>
        <form method="post">
            <button type="submit" class="btn">Install Database</button>
        </form>
    <?php else: ?>
        <a href="/Attachment/index.php" class="btn">Go to Login</a>
    <?php endif; ?>
</div>
</body>
</html>
