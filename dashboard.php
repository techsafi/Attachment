<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/auth.php';
require_login();

$pdo = db();
$role = current_user()['role'];

if ($role === 'student' && current_student_id() <= 0) {
    flash('error', 'Your account is not linked to a student record. Contact the coordinator.');
}

$stats = [];
if (in_array($role, ['admin', 'coordinator'], true)) {
    $stats = [
        'Students' => $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn(),
        'Companies' => $pdo->query('SELECT COUNT(*) FROM companies')->fetchColumn(),
        'Pending Placements' => $pdo->query("SELECT COUNT(*) FROM placements WHERE status='pending'")->fetchColumn(),
        'Approved Placements' => $pdo->query("SELECT COUNT(*) FROM placements WHERE status='approved'")->fetchColumn(),
    ];
} elseif ($role === 'assessor') {
    $uid = current_user()['id'];
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM assessor_assignments WHERE assessor_id = ?');
    $stmt->execute([$uid]);
    $stats['Assigned Students'] = $stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM marks WHERE assessor_id = ?');
    $stmt->execute([$uid]);
    $stats['Marks Submitted'] = $stmt->fetchColumn();
} elseif ($role === 'student' && current_student_id() > 0) {
    $sid = current_student_id();
    $stmt = $pdo->prepare("SELECT status FROM placements WHERE student_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$sid]);
    $placement = $stmt->fetchColumn();
    $stats['Placement Status'] = $placement ?: 'None';
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM submissions WHERE student_id = ?');
    $stmt->execute([$sid]);
    $stats['Documents Uploaded'] = $stmt->fetchColumn();
}

require __DIR__ . '/includes/header.php';
?>
<div class="stats">
    <?php foreach ($stats as $label => $value): ?>
        <div class="stat"><small><?= e($label) ?></small><strong><?= e((string) $value) ?></strong></div>
    <?php endforeach; ?>
</div>
<div class="card">
    <h3 style="margin-bottom:.5rem;color:#1a5276;">Welcome to STVIAMS</h3>
    <p style="font-size:.95rem;">Student Industrial Attachment Management System for <strong>Seme Technical and Vocational College</strong>.</p>
    <ul style="margin:1rem 0 0 1.25rem;font-size:.9rem;">
        <li>Capture and manage student details</li>
        <li>Generate industrial attachment letters</li>
        <li>Approve placement requests</li>
        <li>Assign assessors and record marks</li>
        <li>Submit logbooks and recommendation letters</li>
        <li>Generate reports and completion certificates</li>
    </ul>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
