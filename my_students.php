<?php
$pageTitle = 'My Students';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['assessor']);

$stmt = db()->prepare("
    SELECT s.*, crs.name as course, c.name AS company_name, p.start_date, p.end_date,
           m.total, m.grade
    FROM assessor_assignments aa
    JOIN students s ON s.id = aa.student_id
    JOIN courses crs ON crs.id = s.course_id
    LEFT JOIN placements p ON p.student_id = s.id AND p.status = 'approved'
    LEFT JOIN companies c ON c.id = p.company_id
    LEFT JOIN marks m ON m.student_id = s.id AND m.assessor_id = aa.assessor_id
    WHERE aa.assessor_id = ?
    ORDER BY s.full_name
");
$stmt->execute([current_user()['id']]);
$students = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <table>
        <tr><th>Reg No</th><th>Name</th><th>Course</th><th>Company</th><th>Period</th><th>Marks</th></tr>
        <?php foreach ($students as $s): ?>
        <tr>
            <td><?= e($s['reg_no']) ?></td>
            <td><?= e($s['full_name']) ?></td>
            <td><?= e($s['course']) ?></td>
            <td><?= e($s['company_name'] ?? '-') ?></td>
            <td><?= $s['start_date'] ? e($s['start_date'] . ' to ' . $s['end_date']) : '-' ?></td>
            <td><?= $s['total'] !== null ? (int) $s['total'] . ' (' . e($s['grade']) . ')' : 'Not submitted' ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <p style="margin-top:1rem;"><a href="/Attachment/marks.php" class="btn">Enter Marks</a></p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
