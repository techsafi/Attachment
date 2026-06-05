<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM submissions WHERE id = ?');
$stmt->execute([$id]);
$sub = $stmt->fetch();
if (!$sub) {
    http_response_code(404);
    exit('Not found');
}

$user = current_user();
if ($user['role'] === 'student' && (int) $user['student_id'] !== (int) $sub['student_id']) {
    http_response_code(403);
    exit('Access denied');
}

$path = __DIR__ . '/uploads/' . $sub['file_name'];
if (!is_file($path)) {
    http_response_code(404);
    exit('File missing');
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($sub['original_name']) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
