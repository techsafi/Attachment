<?php
$pageTitle = 'My Certificates';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['student']);

$studentId = current_student_id();
if (!$studentId) {
    flash('error', 'Your account is not linked to a student record. Contact the coordinator.');
    redirect('/Attachment/dashboard.php');
}

$pdo = db();
$stmt = $pdo->prepare(
    "SELECT s.*, crs.name as course, d.name as department, c.name AS company_name, p.start_date, p.end_date, p.status AS placement_status,"
    . " ROUND(AVG(m.total)) AS avg_mark, MAX(m.grade) AS grade,"
    . " SUM(sub.submission_type = 'logbook' AND sub.status = 'approved') AS approved_logbook,"
    . " SUM(sub.submission_type = 'recommendation' AND sub.status = 'approved') AS approved_recommendation,"
    . " COUNT(DISTINCT m.id) AS mark_count,"
    . " COALESCE(sc.is_completed, 0) AS is_completed,"
    . " COALESCE(sc.period_bypassed, 0) AS period_bypassed"
    . " FROM students s"
    . " JOIN placements p ON p.student_id = s.id"
    . " JOIN companies c ON c.id = p.company_id"
    . " JOIN courses crs ON crs.id = s.course_id"
    . " JOIN departments d ON d.id = s.department_id"
    . " JOIN marks m ON m.student_id = s.id"
    . " LEFT JOIN submissions sub ON sub.student_id = s.id"
    . " LEFT JOIN student_completion sc ON sc.student_id = s.id"
    . " WHERE s.id = ?"
    . " GROUP BY s.id, s.reg_no, s.full_name, s.gender, crs.name, d.name, s.phone, s.email, s.level, c.name, p.start_date, p.end_date, p.status, sc.is_completed, sc.period_bypassed"
);
$stmt->execute([$studentId]);
$student = $stmt->fetch();

$eligible = false;
$reasons = [];
if ($student) {
    $placementApproved = $student['placement_status'] === 'approved';
    $placementEnded = strtotime($student['end_date']) <= strtotime(date('Y-m-d'));
    $logbookApproved = (int) $student['approved_logbook'] > 0;
    $recommendationApproved = (int) $student['approved_recommendation'] > 0;
    $passMark = (int) $student['avg_mark'] >= 50;
    $hasMarks = (int) $student['mark_count'] > 0;
    $isCompleted = (int) $student['is_completed'] === 1;
    $periodBypassed = (int) $student['period_bypassed'] === 1;

    // Eligible if:
    // 1. Is explicitly marked as completed, OR
    // 2. Has all requirements: placement approved, period ended (or bypassed), approved submissions, passing marks
    $eligible = $placementApproved && $hasMarks && $passMark && (
        $isCompleted || 
        ($logbookApproved && $recommendationApproved && ($placementEnded || $periodBypassed))
    );

    if (!$placementApproved) {
        $reasons[] = 'Placement is not approved yet.';
    }
    if (!$isCompleted && !$placementEnded && !$periodBypassed) {
        $reasons[] = 'Attachment period is not yet complete (ends on ' . e($student['end_date']) . ').';
    }
    if (!$isCompleted && !$logbookApproved) {
        $reasons[] = 'Logbook has not been approved yet.';
    }
    if (!$isCompleted && !$recommendationApproved) {
        $reasons[] = 'Recommendation letter has not been approved yet.';
    }
    if (!$hasMarks) {
        $reasons[] = 'Marks have not been submitted yet.';
    } elseif (!$passMark) {
        $reasons[] = 'Overall score is below 50%.';
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">My Certificates</h3>
    <?php if ($student): ?>
        <?php if ($eligible): ?>
            <p>Your certificate is ready. Click the button below to view or print your completion certificate.</p>
            <div class="actions">
                <a class="btn" href="/Attachment/print_certificate.php?student_id=<?= (int) $studentId ?>" target="_blank">View Completion Certificate</a>
            </div>
        <?php else: ?>
            <p>Your certificate is not available yet.</p>
            <p>Complete the remaining requirements below to receive your completion certificate:</p>
            <ul>
                <?php foreach ($reasons as $reason): ?>
                    <li><?= e($reason) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <div style="margin-top:1.5rem;">
            <p><strong>Student:</strong> <?= e($student['full_name']) ?></p>
            <p><strong>Admission No:</strong> <?= e($student['reg_no']) ?></p>
            <p><strong>Course:</strong> <?= e($student['course']) ?></p>
            <p><strong>Company:</strong> <?= e($student['company_name']) ?></p>
            <p><strong>Period:</strong> <?= e($student['start_date']) ?> – <?= e($student['end_date']) ?></p>
            <p><strong>Overall Score:</strong> <?= (int) $student['avg_mark'] ?>% (<?= e($student['grade']) ?>)</p>
        </div>
    <?php else: ?>
        <p>No certificate is available yet.</p>
        <p>To qualify, your placement must be approved, your attachment period completed, both logbook and recommendation must be approved, and your average mark must be 50% or higher.</p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php';
