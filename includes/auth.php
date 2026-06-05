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
