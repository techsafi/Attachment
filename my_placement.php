<?php
$pageTitle = 'My Placement';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['student']);

$studentId = current_user()['student_id'] ?? null;
if (!$studentId) {
    flash('error', 'Account not linked to student record.');
    redirect('/Attachment/dashboard.php');
}

$stmt = db()->prepare("
    SELECT p.*, c.name AS company_name, c.address, c.contact_person, c.phone AS company_phone
    FROM placements p
    JOIN companies c ON c.id = p.company_id
    WHERE p.student_id = ?
    ORDER BY p.id DESC LIMIT 1
");
$stmt->execute([$studentId]);
$placement = $stmt->fetch();

require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <?php if ($placement): ?>
    <p><strong>Company:</strong> <?= e($placement['company_name']) ?></p>
    <p><strong>Address:</strong> <?= e($placement['address']) ?></p>
    <p><strong>Contact:</strong> <?= e($placement['contact_person']) ?> — <?= e($placement['company_phone']) ?></p>
    <p><strong>Period:</strong> <?= e($placement['start_date']) ?> to <?= e($placement['end_date']) ?></p>
    <p><strong>Status:</strong> <span class="badge badge-<?= e($placement['status']) ?>"><?= e(ucfirst($placement['status'])) ?></span></p>
    <?php if ($placement['rejection_reason']): ?>
        <p><strong>Reason:</strong> <?= e($placement['rejection_reason']) ?></p>
    <?php endif; ?>
    <?php else: ?>
    <p>No placement record found. Contact the attachment coordinator.</p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
