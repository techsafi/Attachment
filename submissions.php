<?php
$pageTitle = 'Submissions';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['admin', 'coordinator']);

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->prepare('UPDATE submissions SET status = ? WHERE id = ?')->execute([$_POST['status'], (int) $_POST['id']]);
    flash('success', 'Submission status updated.');
    redirect('/Attachment/submissions.php');
}

$submissions = $pdo->query("
    SELECT sub.*, s.full_name, s.reg_no
    FROM submissions sub
    JOIN students s ON s.id = sub.student_id
    ORDER BY sub.submitted_at DESC
")->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Student Logbooks & Recommendation Letters</h3>
    <table>
        <tr><th>Student</th><th>Type</th><th>File</th><th>Submitted</th><th>Status</th><th>Actions</th></tr>
        <?php foreach ($submissions as $sub): ?>
        <tr>
            <td><?= e($sub['full_name']) ?> (<?= e($sub['reg_no']) ?>)</td>
            <td><?= e(ucfirst($sub['submission_type'])) ?></td>
            <td><?= e($sub['original_name']) ?></td>
            <td><?= e($sub['submitted_at']) ?></td>
            <td><span class="badge badge-<?= $sub['status'] === 'approved' ? 'approved' : 'pending' ?>"><?= e(ucfirst($sub['status'])) ?></span></td>
            <td class="actions">
                <a href="/Attachment/download.php?id=<?= (int) $sub['id'] ?>" class="btn btn-sm">Download</a>
                <form method="post" style="display:inline">
                    <input type="hidden" name="id" value="<?= (int) $sub['id'] ?>">
                    <select name="status" onchange="this.form.submit()">
                        <option value="pending" <?= $sub['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="reviewed" <?= $sub['status'] === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                        <option value="approved" <?= $sub['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                    </select>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$submissions): ?><tr><td colspan="6">No submissions yet.</td></tr><?php endif; ?>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
