<?php
$pageTitle = 'Reports';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['admin', 'coordinator']);

$pdo = db();
$report = $_GET['report'] ?? 'students';

$data = [];
$title = 'Report';

switch ($report) {
    case 'placements':
        $title = 'Placements Report';
        $data = $pdo->query("
            SELECT s.reg_no, s.full_name, crs.name as course, c.name AS company, p.start_date, p.end_date, p.status
            FROM placements p
            JOIN students s ON s.id = p.student_id
            JOIN courses crs ON crs.id = s.course_id
            JOIN companies c ON c.id = p.company_id
            ORDER BY p.status, s.full_name
        ")->fetchAll();
        break;
    case 'marks':
        $title = 'Marks Report';
        $data = $pdo->query("
            SELECT s.reg_no, s.full_name, crs.name as course, u.full_name AS assessor,
                   m.practical, m.logbook, m.attitude, m.total, m.grade
            FROM marks m
            JOIN students s ON s.id = m.student_id
            JOIN courses crs ON crs.id = s.course_id
            JOIN users u ON u.id = m.assessor_id
            ORDER BY s.full_name
        ")->fetchAll();
        break;
    case 'submissions':
        $title = 'Submissions Report';
        $data = $pdo->query("
            SELECT s.reg_no, s.full_name, sub.submission_type, sub.original_name, sub.status, sub.submitted_at
            FROM submissions sub
            JOIN students s ON s.id = sub.student_id
            ORDER BY sub.submitted_at DESC
        ")->fetchAll();
        break;
    default:
        $report = 'students';
        $title = 'Students Report';
        $data = $pdo->query('SELECT s.reg_no, s.full_name, s.gender, crs.name as course, d.name as department, s.level, s.phone FROM students s JOIN courses crs ON crs.id = s.course_id JOIN departments d ON d.id = s.department_id ORDER BY s.full_name')->fetchAll();
}

require __DIR__ . '/includes/header.php';
?>
<div class="card no-print">
    <p style="margin-bottom:1rem;">Select report type:</p>
    <div class="actions">
        <a href="?report=students" class="btn <?= $report === 'students' ? '' : 'btn-secondary' ?>">Students</a>
        <a href="?report=placements" class="btn <?= $report === 'placements' ? '' : 'btn-secondary' ?>">Placements</a>
        <a href="?report=marks" class="btn <?= $report === 'marks' ? '' : 'btn-secondary' ?>">Marks</a>
        <a href="?report=submissions" class="btn <?= $report === 'submissions' ? '' : 'btn-secondary' ?>">Submissions</a>
        <button onclick="window.print()" class="btn btn-success">Print Report</button>
    </div>
</div>
<div class="card print-area">
    <div class="print-header">
        <h2>SEME TECHNICAL AND VOCATIONAL COLLEGE</h2>
        <h3><?= e($title) ?></h3>
        <p>Generated: <?= e(date('d F Y H:i')) ?></p>
    </div>
    <?php if ($data): ?>
    <table>
        <tr>
            <?php foreach (array_keys($data[0]) as $col): ?>
                <th><?= e(ucwords(str_replace('_', ' ', $col))) ?></th>
            <?php endforeach; ?>
        </tr>
        <?php foreach ($data as $row): ?>
        <tr>
            <?php foreach ($row as $val): ?>
                <td><?= e((string) $val) ?></td>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
    </table>
    <p style="margin-top:1rem;font-size:.85rem;">Total records: <?= count($data) ?></p>
    <?php else: ?>
        <p>No data for this report.</p>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
