<?php
$pageTitle = 'Certificates';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['admin', 'coordinator']);

$pdo = db();

// Students with approved placement, marks >= 50, and both submission types approved
// OR students marked as completed by admin
$sql = "SELECT s.id, s.reg_no, s.full_name, crs.name as course, d.name as department,"
    . " c.name AS company_name, p.start_date, p.end_date,"
    . " ROUND(AVG(m.total)) AS avg_mark, MAX(m.grade) AS grade,"
    . " COALESCE(sc.is_completed, 0) as is_completed, COALESCE(sc.period_bypassed, 0) as period_bypassed"
    . " FROM students s"
    . " JOIN placements p ON p.student_id = s.id AND p.status = 'approved'"
    . " JOIN companies c ON c.id = p.company_id"
    . " JOIN courses crs ON crs.id = s.course_id"
    . " JOIN departments d ON d.id = s.department_id"
    . " JOIN marks m ON m.student_id = s.id"
    . " LEFT JOIN student_completion sc ON sc.student_id = s.id"
    . " WHERE ("
    . " (p.end_date <= CURDATE() OR sc.period_bypassed = 1)"
    . " AND EXISTS ("
    . " SELECT 1 FROM submissions sub"
    . " WHERE sub.student_id = s.id"
    . " AND sub.submission_type = 'logbook'"
    . " AND sub.status = 'approved'"
    . " )"
    . " AND EXISTS ("
    . " SELECT 1 FROM submissions sub2"
    . " WHERE sub2.student_id = s.id"
    . " AND sub2.submission_type = 'recommendation'"
    . " AND sub2.status = 'approved'"
    . " )"
    . " )"
    . " OR (sc.is_completed = 1)"
    . " GROUP BY s.id, s.reg_no, s.full_name, crs.name, d.name, c.name, p.start_date, p.end_date, sc.is_completed, sc.period_bypassed"
    . " HAVING avg_mark >= 50";
$eligible = $pdo->query($sql)->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Completion Certificates</h3>
    <p style="font-size:.9rem;margin-bottom:1rem;">Students with approved placement, completed period, and passing marks (50+) qualify for a certificate.</p>
    <table>
        <tr><th>Reg No</th><th>Name</th><th>Course</th><th>Company</th><th>Period</th><th>Mark</th><th></th></tr>
        <?php foreach ($eligible as $e): ?>
        <tr>
            <td><?= e($e['reg_no']) ?></td>
            <td><?= e($e['full_name']) ?></td>
            <td><?= e($e['course']) ?></td>
            <td><?= e($e['company_name']) ?></td>
            <td><?= e($e['start_date']) ?> – <?= e($e['end_date']) ?></td>
            <td><?= (int) $e['avg_mark'] ?> (<?= e($e['grade']) ?>)</td>
            <td><a href="/Attachment/print_certificate.php?student_id=<?= (int) $e['id'] ?>" class="btn btn-sm" target="_blank">Generate Certificate</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$eligible): ?><tr><td colspan="7">No eligible students yet. Ensure placements are approved, marks entered, and attachment period ended.</td></tr><?php endif; ?>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
