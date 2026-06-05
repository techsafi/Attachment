<?php
$pageTitle = 'My Attachment';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['student']);

$studentId = current_user()['student_id'] ?? null;
if (!$studentId) {
    flash('error', 'Your account is not linked to a student record. Contact the coordinator.');
    redirect('/Attachment/dashboard.php');
}

$pdo = db();

$p = $pdo->prepare("
    SELECT p.*, c.name AS company_name, c.address, c.contact_person, c.phone AS company_phone, c.email AS company_email
    FROM placements p
    JOIN companies c ON c.id = p.company_id
    WHERE p.student_id = ?
    ORDER BY p.id DESC
    LIMIT 1
");
$p->execute([$studentId]);
$placement = $p->fetch();

$a = $pdo->prepare("
    SELECT u.full_name, u.username, aa.assigned_at
    FROM assessor_assignments aa
    JOIN users u ON u.id = aa.assessor_id
    WHERE aa.student_id = ?
    ORDER BY aa.assigned_at DESC
");
$a->execute([$studentId]);
$assessors = $a->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Placement Details</h3>
    <?php if ($placement): ?>
        <p><strong>Industry/Company:</strong> <?= e($placement['company_name']) ?></p>
        <p><strong>Address:</strong> <?= e($placement['address']) ?></p>
        <p><strong>Contact:</strong> <?= e($placement['contact_person']) ?> — <?= e($placement['company_phone']) ?></p>
        <p><strong>Email:</strong> <?= e($placement['company_email']) ?></p>
        <p><strong>Period:</strong> <?= e($placement['start_date']) ?> to <?= e($placement['end_date']) ?></p>
        <p><strong>Status:</strong> <span class="badge badge-<?= e($placement['status']) ?>"><?= e(ucfirst($placement['status'])) ?></span></p>
        <?php if (!empty($placement['rejection_reason'])): ?>
            <p><strong>Rejection reason:</strong> <?= e($placement['rejection_reason']) ?></p>
        <?php endif; ?>
    <?php else: ?>
        <p>No placement found yet. Contact the attachment coordinator.</p>
    <?php endif; ?>
</div>

<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Assigned Assessor(s)</h3>
    <?php if ($assessors): ?>
        <table>
            <tr><th>Name</th><th>Username</th><th>Assigned At</th></tr>
            <?php foreach ($assessors as $as): ?>
                <tr>
                    <td><?= e($as['full_name']) ?></td>
                    <td><?= e($as['username']) ?></td>
                    <td><?= e($as['assigned_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No assessor assigned yet.</p>
    <?php endif; ?>
</div>

<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Quick Links</h3>
    <div class="actions">
        <a class="btn" href="/Attachment/upload.php">Submit Logbook / Recommendation</a>
        <a class="btn btn-secondary" href="/Attachment/my_marks.php">View Marks</a>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>

