<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function require_login(): void
{
    if (empty($_SESSION['user'])) {
        redirect('/Attachment/index.php');
    }
}

function current_user(): array
{
    return $_SESSION['user'];
}

function current_student_id(): int
{
    $user = current_user();
    $studentId = (int) ($user['student_id'] ?? 0);
    if ($studentId > 0) {
        return $studentId;
    }

    if ($user['role'] === 'student' && !empty($user['id'])) {
        $stmt = db()->prepare('SELECT student_id FROM users WHERE id = ?');
        $stmt->execute([(int) $user['id']]);
        $row = $stmt->fetch();
        if ($row && !empty($row['student_id'])) {
            $_SESSION['user']['student_id'] = (int) $row['student_id'];
            return $_SESSION['user']['student_id'];
        }
    }

    return 0;
}
