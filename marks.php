<?php
$pageTitle = 'Enter Marks';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['assessor']);

$pdo = db();
$uid = current_user()['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $practical = (int) $_POST['practical'];
    $logbook = (int) $_POST['logbook'];
    $attitude = (int) $_POST['attitude'];
    $total = (int) round(($practical + $logbook + $attitude) / 3);
    $grade = grade_from_total($total);
    $studentId = (int) $_POST['student_id'];

    $check = $pdo->prepare('SELECT id FROM assessor_assignments WHERE student_id = ? AND assessor_id = ?');
    $check->execute([$studentId, $uid]);
    if (!$check->fetch()) {
        flash('error', 'You are not assigned to this student.');
    } else {
        $stmt = $pdo->prepare('
            INSERT INTO marks (student_id, assessor_id, practical, logbook, attitude, total, grade, remarks)
            VALUES (?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE practical=VALUES(practical), logbook=VALUES(logbook),
            attitude=VALUES(attitude), total=VALUES(total), grade=VALUES(grade), remarks=VALUES(remarks), submitted_at=NOW()
        ');
        $stmt->execute([$studentId, $uid, $practical, $logbook, $attitude, $total, $grade, trim($_POST['remarks'] ?? '')]);
        flash('success', 'Marks submitted successfully. Total: ' . $total . ' Grade: ' . $grade);
    }
    redirect('/Attachment/marks.php');
}

$assigned = $pdo->prepare("
    SELECT s.id, s.reg_no, s.full_name, crs.name as course,
           m.practical, m.logbook, m.attitude, m.total, m.grade
    FROM assessor_assignments aa
    JOIN students s ON s.id = aa.student_id
    JOIN courses crs ON crs.id = s.course_id
    LEFT JOIN marks m ON m.student_id = s.id AND m.assessor_id = aa.assessor_id
    WHERE aa.assessor_id = ?
    ORDER BY s.full_name
");
$assigned->execute([$uid]);
$students = $assigned->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<?php if (!$students): ?>
<div class="alert alert-info">No students assigned to you yet. Contact the coordinator.</div>
<?php else: ?>
<?php foreach ($students as $s): ?>
<div class="card">
    <h3 style="color:#1a5276;margin-bottom:.5rem;"><?= e($s['full_name']) ?> (<?= e($s['reg_no']) ?>)</h3>
    <p style="font-size:.85rem;margin-bottom:1rem;"><?= e($s['course']) ?>
        <?php if ($s['total'] !== null): ?> — Current: <?= (int) $s['total'] ?> (<?= e($s['grade']) ?>)<?php endif; ?>
    </p>
    <form method="post" class="form-grid">
        <input type="hidden" name="student_id" value="<?= (int) $s['id'] ?>">
        <div><label>Practical (0-100)</label><input type="number" name="practical" min="0" max="100" value="<?= (int) ($s['practical'] ?? 0) ?>" required></div>
        <div><label>Logbook (0-100)</label><input type="number" name="logbook" min="0" max="100" value="<?= (int) ($s['logbook'] ?? 0) ?>" required></div>
        <div><label>Attitude (0-100)</label><input type="number" name="attitude" min="0" max="100" value="<?= (int) ($s['attitude'] ?? 0) ?>" required></div>
        <div style="grid-column:1/-1"><label>Remarks</label><textarea name="remarks"></textarea></div>
        <div><button type="submit" class="btn">Submit Marks</button></div>
    </form>
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
