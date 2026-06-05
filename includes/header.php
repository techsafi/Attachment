<?php
require_login();
$user = current_user();
$role = $user['role'];
$current = basename($_SERVER['PHP_SELF']);

$menus = [
    'admin' => [
        ['dashboard.php', 'Dashboard'],
        ['students.php', 'Students'],
        ['companies.php', 'Companies'],
        ['placements.php', 'Placements'],
        ['letters.php', 'Industrial Letters'],
        ['assessors.php', 'Assessors & Marks'],
        ['submissions.php', 'Submissions'],
        ['reports.php', 'Reports'],
        ['certificates.php', 'Certificates'],
        ['users.php', 'Users'],
    ],
    'coordinator' => [
        ['dashboard.php', 'Dashboard'],
        ['students.php', 'Students'],
        ['companies.php', 'Companies'],
        ['placements.php', 'Placements'],
        ['letters.php', 'Industrial Letters'],
        ['assessors.php', 'Assessors & Marks'],
        ['submissions.php', 'Submissions'],
        ['reports.php', 'Reports'],
        ['certificates.php', 'Certificates'],
    ],
    'assessor' => [
        ['dashboard.php', 'Dashboard'],
        ['my_students.php', 'My Students'],
        ['marks.php', 'Enter Marks'],
    ],
    'student' => [
        ['dashboard.php', 'Dashboard'],
        ['my_attachment.php', 'My Attachment'],
        ['upload.php', 'Upload Documents'],
        ['my_marks.php', 'My Marks'],
    ],
];
$nav = $menus[$role] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'STVIAMS') ?> - STVIAMS</title>
    <link rel="stylesheet" href="/Attachment/assets/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar no-print">
        <div class="brand">
            <strong>STVIAMS</strong>
            <small>Seme TVC</small>
        </div>
        <nav>
            <?php foreach ($nav as [$file, $label]): ?>
                <a href="/Attachment/<?= e($file) ?>" class="<?= $current === $file ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
            <a href="/Attachment/logout.php">Logout</a>
        </nav>
    </aside>
    <main class="main">
        <div class="topbar no-print">
            <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
            <span><?= e($user['full_name']) ?> (<?= e(ucfirst($role)) ?>)</span>
        </div>
        <?php if ($f = get_flash()): ?>
            <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
        <?php endif; ?>
