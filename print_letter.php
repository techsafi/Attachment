<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['admin', 'coordinator']);

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare("
    SELECT l.*, s.full_name, s.reg_no, s.course, s.department,
           c.name AS company_name, c.address AS company_address, c.contact_person,
           p.start_date, p.end_date
    FROM industrial_letters l
    JOIN placements p ON p.id = l.placement_id
    JOIN students s ON s.id = p.student_id
    JOIN companies c ON c.id = p.company_id
    WHERE l.id = ?
");
$stmt->execute([$id]);
$letter = $stmt->fetch();
if (!$letter) {
    flash('error', 'Letter not found.');
    redirect('/Attachment/letters.php');
}

$titles = [
    'introduction' => 'LETTER OF INTRODUCTION',
    'placement' => 'INDUSTRIAL ATTACHMENT PLACEMENT LETTER',
    'release' => 'LETTER OF RELEASE',
];
$body = [
    'introduction' => "This is to introduce our student <strong>{$letter['full_name']}</strong> (Reg. No: {$letter['reg_no']}) undertaking <strong>{$letter['course']}</strong> in the {$letter['department']} department. The student is seeking industrial attachment at your organization from <strong>{$letter['start_date']}</strong> to <strong>{$letter['end_date']}</strong>. We kindly request you to accept the student for practical training.",
    'placement' => "We confirm the placement of <strong>{$letter['full_name']}</strong> (Reg. No: {$letter['reg_no']}) at <strong>{$letter['company_name']}</strong> for industrial attachment from <strong>{$letter['start_date']}</strong> to <strong>{$letter['end_date']}</strong>. The student will report as scheduled and abide by your organization's rules.",
    'release' => "This letter confirms that <strong>{$letter['full_name']}</strong> (Reg. No: {$letter['reg_no']}) has completed industrial attachment at <strong>{$letter['company_name']}</strong> for the period <strong>{$letter['start_date']}</strong> to <strong>{$letter['end_date']}</strong>. We appreciate your support in training our student.",
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e($letter['reference_no']) ?></title>
    <link rel="stylesheet" href="/Attachment/assets/style.css">
</head>
<body>
<div class="no-print" style="padding:1rem;text-align:center;">
    <button onclick="window.print()" class="btn">Print Letter</button>
    <a href="/Attachment/letters.php" class="btn btn-secondary">Back</a>
</div>
<div class="print-area">
    <div class="print-header">
        <h2>SEME TECHNICAL AND VOCATIONAL COLLEGE</h2>
        <p>P.O. Box — Seme, Kenya | Industrial Attachment Office</p>
        <h3 style="margin-top:1rem;"><?= e($titles[$letter['letter_type']] ?? 'LETTER') ?></h3>
        <p><strong>Ref:</strong> <?= e($letter['reference_no']) ?> &nbsp;|&nbsp; <strong>Date:</strong> <?= e(date('d F Y', strtotime($letter['generated_at']))) ?></p>
    </div>
    <p><strong>To:</strong><br>
    The Manager<br>
    <?= e($letter['company_name']) ?><br>
    <?= nl2br(e($letter['company_address'] ?? '')) ?>
    <?php if ($letter['contact_person']): ?><br>Attn: <?= e($letter['contact_person']) ?><?php endif; ?>
    </p>
    <p>Dear Sir/Madam,</p>
    <p><?= $body[$letter['letter_type']] ?? '' ?></p>
    <p>Thank you for your cooperation.</p>
    <p style="margin-top:3rem;">Yours faithfully,<br><br>
    _________________________<br>
    <strong>Industrial Attachment Coordinator</strong><br>
    Seme Technical and Vocational College</p>
</div>
</body>
</html>
