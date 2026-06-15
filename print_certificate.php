<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['admin', 'coordinator', 'student']);

$user = current_user();
if ($user['role'] === 'student') {
    $studentId = current_student_id();
    if ($studentId <= 0) {
        http_response_code(403);
        exit('Access denied');
    }
} else {
    $studentId = (int) ($_GET['student_id'] ?? 0);
    if ($studentId <= 0) {
        http_response_code(403);
        exit('Access denied');
    }
}

$stmt = db()->prepare("
        SELECT s.*, crs.name as course, d.name as department, c.name AS company_name, p.start_date, p.end_date,
                     ROUND(AVG(m.total)) AS avg_mark, MAX(m.grade) AS grade,
                     COALESCE(sc.is_completed, 0) AS is_completed,
                     COALESCE(sc.period_bypassed, 0) AS period_bypassed
        FROM students s
        JOIN placements p ON p.student_id = s.id AND p.status = 'approved'
        JOIN companies c ON c.id = p.company_id
        JOIN courses crs ON crs.id = s.course_id
        JOIN departments d ON d.id = s.department_id
        JOIN marks m ON m.student_id = s.id
        LEFT JOIN student_completion sc ON sc.student_id = s.id
        WHERE s.id = ? AND (p.end_date <= CURDATE() OR sc.period_bypassed = 1 OR sc.is_completed = 1)
            AND EXISTS (
                    SELECT 1 FROM submissions sub
                    WHERE sub.student_id = s.id
                        AND sub.submission_type = 'logbook'
                        AND sub.status = 'approved'
            )
            AND EXISTS (
                    SELECT 1 FROM submissions sub2
                    WHERE sub2.student_id = s.id
                        AND sub2.submission_type = 'recommendation'
                        AND sub2.status = 'approved'
            )
        GROUP BY s.id, s.reg_no, s.full_name, s.gender, crs.name, d.name, s.phone, s.email, s.level, c.name, p.start_date, p.end_date, sc.is_completed, sc.period_bypassed
        HAVING avg_mark >= 50
");
$stmt->execute([$studentId]);
$student = $stmt->fetch();
if (!$student) {
    flash('error', 'Student not eligible for certificate.');
    redirect('/Attachment/certificates.php');
}
$certNo = 'CERT/' . date('Y') . '/' . str_pad((string) $studentId, 4, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Certificate - <?= e($student['full_name']) ?></title>
    <link rel="stylesheet" href="/Attachment/assets/style.css">
    <style>
    .certificate {
        border: 8px double #1a5276;
        padding: 3rem 2rem;
        text-align: center;
        max-width: 700px;
        margin: 2rem auto;
    }

    .certificate h1 {
        font-size: 1.8rem;
        color: #1a5276;
        margin-bottom: .5rem;
    }

    .certificate h2 {
        font-size: 1.2rem;
        color: #2874a6;
        margin: 1.5rem 0;
    }

    .certificate .name {
        font-size: 1.5rem;
        font-weight: bold;
        color: #154360;
        margin: 1rem 0;
        text-decoration: underline;
    }
    </style>
</head>

<body>
    <div class="no-print" style="padding:1rem;text-align:center;">
        <button onclick="window.print()" class="btn">Print Certificate</button>
        <a href="/Attachment/certificates.php" class="btn btn-secondary">Back</a>
    </div>
    <div class="certificate print-area">
        <h1>SEME TECHNICAL AND VOCATIONAL COLLEGE</h1>
        <p>Industrial Attachment Programme</p>
        <h2>CERTIFICATE OF COMPLETION</h2>
        <p>Certificate No: <strong><?= e($certNo) ?></strong></p>
        <p>This is to certify that</p>
        <p class="name"><?= e($student['full_name']) ?></p>
        <p>Registration No: <strong><?= e($student['reg_no']) ?></strong></p>
        <p>has successfully completed industrial attachment in <strong><?= e($student['course']) ?></strong>
            at <strong><?= e($student['company_name']) ?></strong></p>
        <p>from <strong><?= e(date('d F Y', strtotime($student['start_date']))) ?></strong>
            to <strong><?= e(date('d F Y', strtotime($student['end_date']))) ?></strong></p>
        <p>with an overall score of <strong><?= (int) $student['avg_mark'] ?>%</strong> (Grade:
            <?= e($student['grade']) ?>)</p>
        <p style="margin-top:2.5rem;">Date of Issue: <?= e(date('d F Y')) ?></p>
        <p style="margin-top:2rem;">
            _________________________ &nbsp;&nbsp;&nbsp;&nbsp; _________________________<br>
            Principal
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            Attachment Coordinator
        </p>
    </div>
</body>

</html>