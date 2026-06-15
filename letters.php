<?php
$pageTitle = 'Industrial Letters';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['admin', 'coordinator']);

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate') {
    $placementId = (int) $_POST['placement_id'];
    $type = $_POST['letter_type'];
    $ref = letter_ref($type, $placementId);
    $stmt = $pdo->prepare('INSERT INTO industrial_letters (placement_id, letter_type, reference_no, generated_by) VALUES (?,?,?,?)');
    $stmt->execute([$placementId, $type, $ref, current_user()['id']]);
    redirect('/Attachment/print_letter.php?id=' . $pdo->lastInsertId());
}

$approved = $pdo->query("
    SELECT p.id, s.full_name, s.reg_no, crs.name as course, c.name AS company_name, p.start_date, p.end_date
    FROM placements p
    JOIN students s ON s.id = p.student_id
    JOIN courses crs ON crs.id = s.course_id
    JOIN companies c ON c.id = p.company_id
    WHERE p.status = 'approved'
    ORDER BY s.full_name
")->fetchAll();

$history = $pdo->query("
    SELECT l.*, s.full_name, c.name AS company_name
    FROM industrial_letters l
    JOIN placements p ON p.id = l.placement_id
    JOIN students s ON s.id = p.student_id
    JOIN companies c ON c.id = p.company_id
    ORDER BY l.generated_at DESC LIMIT 20
")->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Generate Industrial Attachment Letter</h3>
    <p style="font-size:.9rem;margin-bottom:1rem;">Generate introduction, placement, or release letters for approved placements.</p>
    <?php if (!$approved): ?>
        <div class="alert alert-info">No approved placements yet. Approve placements first.</div>
    <?php else: ?>
    <form method="post" class="form-grid">
        <input type="hidden" name="action" value="generate">
        <div><label>Approved Placement</label>
            <select name="placement_id" required>
                <?php foreach ($approved as $a): ?>
                    <option value="<?= (int) $a['id'] ?>">
                        <?= e($a['full_name'] . ' → ' . $a['company_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><label>Letter Type</label>
            <select name="letter_type" required>
                <option value="introduction">Letter of Introduction</option>
                <option value="placement">Placement Letter</option>
                <option value="release">Release Letter</option>
            </select>
        </div>
        <div><button type="submit" class="btn">Generate & Print</button></div>
    </form>
    <?php endif; ?>
</div>
<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Recently Generated</h3>
    <table>
        <tr><th>Ref No</th><th>Student</th><th>Company</th><th>Type</th><th>Date</th><th></th></tr>
        <?php foreach ($history as $h): ?>
        <tr>
            <td><?= e($h['reference_no']) ?></td>
            <td><?= e($h['full_name']) ?></td>
            <td><?= e($h['company_name']) ?></td>
            <td><?= e(ucfirst($h['letter_type'])) ?></td>
            <td><?= e($h['generated_at']) ?></td>
            <td><a href="/Attachment/print_letter.php?id=<?= (int) $h['id'] ?>" class="btn btn-sm">View</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
