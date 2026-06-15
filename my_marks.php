<?php
$pageTitle = 'My Marks';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['student']);

$studentId = current_student_id();
if (!$studentId) redirect('/Attachment/dashboard.php');

$marks = db()->prepare("
    SELECT m.*, u.full_name AS assessor_name
    FROM marks m
    JOIN users u ON u.id = m.assessor_id
    WHERE m.student_id = ?
");
$marks->execute([$studentId]);
$rows = $marks->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <?php if ($rows): ?>
    <table>
        <tr><th>Assessor</th><th>Practical</th><th>Logbook</th><th>Attitude</th><th>Total</th><th>Grade</th><th>Remarks</th></tr>
        <?php foreach ($rows as $m): ?>
        <tr>
            <td><?= e($m['assessor_name']) ?></td>
            <td><?= (int) $m['practical'] ?></td>
            <td><?= (int) $m['logbook'] ?></td>
            <td><?= (int) $m['attitude'] ?></td>
            <td><strong><?= (int) $m['total'] ?></strong></td>
            <td><strong><?= e($m['grade']) ?></strong></td>
            <td><?= e($m['remarks']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php if ((int) $rows[0]['total'] >= 50): ?>
        <p style="margin-top:1rem;"><a href="/Attachment/my_certificates.php" class="btn">View Completion Certificate</a></p>
    <?php endif; ?>
    <?php else: ?>
    <p>Marks not yet submitted by your assessor.</p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
