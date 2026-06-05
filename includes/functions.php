<?php

function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function grade_from_total(int $total): string
{
    if ($total >= 70) return 'A';
    if ($total >= 60) return 'B';
    if ($total >= 50) return 'C';
    if ($total >= 40) return 'D';
    return 'F';
}

function letter_ref(string $type, int $id): string
{
    $prefix = ['introduction' => 'INT', 'placement' => 'PLC', 'release' => 'REL'][$type] ?? 'LTR';
    return $prefix . '/' . date('Y') . '/' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
}

function can_manage(): bool
{
    return in_array($_SESSION['user']['role'] ?? '', ['admin', 'coordinator'], true);
}

function require_role(array $roles): void
{
    if (!in_array($_SESSION['user']['role'] ?? '', $roles, true)) {
        flash('error', 'Access denied.');
        redirect('/Attachment/dashboard.php');
    }
}
