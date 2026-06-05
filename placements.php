<?php
$pageTitle = 'Placements';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['admin', 'coordinator']);

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmt = $pdo->prepare('INSERT INTO placements (student_id, company_id, start_date, end_date, status) VALUES (?,?,?,?,"pending")');
        $stmt->execute([(int) $_POST['student_id'], (int) $_POST['company_id'], $_POST['start_date'], $_POST['end_date']]);
        flash('success', 'Placement request created (pending approval).');
    } elseif ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE placements SET status='approved', approved_by=?, approved_at=NOW() WHERE id=?");
        $stmt->execute([current_user()['id'], (int) $_POST['id']]);
        flash('success', 'Placement approved.');
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE placements SET status='rejected', approved_by=?, approved_at=NOW(), rejection_reason=? WHERE id=?");
        $stmt->execute([current_user()['id'], trim($_POST['reason'] ?? ''), (int) $_POST['id']]);
        flash('success', 'Placement rejected.');
    }
    redirect('/Attachment/placements.php');
}

$students = $pdo->query('SELECT id, reg_no, full_name FROM students ORDER BY full_name')->fetchAll();
$companies = $pdo->query('SELECT id, name FROM companies ORDER BY name')->fetchAll();
$placements = $pdo->query("
    SELECT p.*, s.reg_no, s.full_name AS student_name, c.name AS company_name, u.full_name AS approver
    FROM placements p
    JOIN students s ON s.id = p.student_id
    JOIN companies c ON c.id = p.company_id
    LEFT JOIN users u ON u.id = p.approved_by
    ORDER BY p.created_at DESC
")->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">New Placement Request</h3>
    <form method="post" class="form-grid">
        <input type="hidden" name="action" value="add">
        <div><label>Student</label>
            <select name="student_id" required>
                <option value="">-- Select --</option>
                <?php foreach ($students as $s): ?>
                    <option value="<?= (int) $s['id'] ?>"><?= e($s['reg_no'] . ' - ' . $s['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><label>Company</label>
            <select name="company_id" required>
                <option value="">-- Select --</option>
                <?php foreach ($companies as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><label>Start Date</label><input type="date" name="start_date" required></div>
        <div><label>End Date</label><input type="date" name="end_date" required></div>
        <div><button type="submit" class="btn">Create Placement</button></div>
    </form>
</div>
<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Placement Approvals</h3>
    <table>
        <tr>
            <th>Student</th><th>Company</th><th>Period</th><th>Status</th><th>Actions</th>
        </tr>
        <?php foreach ($placements as $p): ?>
        <tr>
            <td><?= e($p['student_name']) ?> (<?= e($p['reg_no']) ?>)</td>
            <td><?= e($p['company_name']) ?></td>
            <td><?= e($p['start_date']) ?> to <?= e($p['end_date']) ?></td>
            <td><span class="badge badge-<?= e($p['status']) ?>"><?= e(ucfirst($p['status'])) ?></span></td>
            <td class="actions">
                <?php if ($p['status'] === 'pending'): ?>
                    <form method="post" style="display:inline">
                        <input type="hidden" name="action" value="approve">
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <button class="btn btn-success btn-sm">Approve</button>
                    </form>
                    <form method="post" style="display:inline" onsubmit="var r=prompt('Rejection reason:'); if(!r)return false; this.reason.value=r;">
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <input type="hidden" name="reason" value="">
                        <button class="btn btn-danger btn-sm">Reject</button>
                    </form>
                <?php else: ?>
                    <?= $p['approver'] ? 'By ' . e($p['approver']) : '' ?>
                    <?php if ($p['rejection_reason']): ?><br><small><?= e($p['rejection_reason']) ?></small><?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
