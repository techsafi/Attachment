<?php
$pageTitle = 'Student Completion Management';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['admin', 'coordinator']);

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $studentId = (int) $_POST['student_id'];
    $uid = current_user()['id'];

    try {
        if ($action === 'update_completion') {
            $isCompleted = isset($_POST['is_completed']) ? 1 : 0;
            $periodBypassed = isset($_POST['period_bypassed']) ? 1 : 0;
            $notes = trim($_POST['notes'] ?? '');

            $check = $pdo->prepare('SELECT id FROM student_completion WHERE student_id = ?');
            $check->execute([$studentId]);
            
            if ($check->fetch()) {
                $stmt = $pdo->prepare('
                    UPDATE student_completion 
                    SET is_completed = ?, period_bypassed = ?, completed_by = ?, completed_at = NOW(), notes = ?
                    WHERE student_id = ?
                ');
                $stmt->execute([$isCompleted, $periodBypassed, $uid, $notes, $studentId]);
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO student_completion (student_id, is_completed, period_bypassed, completed_by, notes)
                    VALUES (?, ?, ?, ?, ?)
                ');
                $stmt->execute([$studentId, $isCompleted, $periodBypassed, $uid, $notes]);
            }

            flash('success', 'Student completion status updated successfully.');
        } elseif ($action === 'clear_completion') {
            $pdo->prepare('DELETE FROM student_completion WHERE student_id = ?')->execute([$studentId]);
            flash('success', 'Student completion status cleared.');
        }
        redirect('/Attachment/student_completion.php');
    } catch (PDOException $e) {
        flash('error', 'Database error: ' . $e->getMessage());
        redirect('/Attachment/student_completion.php');
    }
}

// Get students with their latest placement and marks
$students = [];
$tableError = null;
try {
    $students = $pdo->query("
        SELECT s.id, s.reg_no, s.full_name, crs.name as course, 
               (SELECT p.id FROM placements p WHERE p.student_id = s.id ORDER BY p.id DESC LIMIT 1) as placement_id,
               (SELECT p.status FROM placements p WHERE p.student_id = s.id ORDER BY p.id DESC LIMIT 1) as placement_status,
               (SELECT p.start_date FROM placements p WHERE p.student_id = s.id ORDER BY p.id DESC LIMIT 1) as start_date,
               (SELECT p.end_date FROM placements p WHERE p.student_id = s.id ORDER BY p.id DESC LIMIT 1) as end_date,
               (SELECT ROUND(AVG(m.total)) FROM marks m WHERE m.student_id = s.id) as avg_mark,
               COALESCE(sc.is_completed, 0) as is_completed, 
               COALESCE(sc.period_bypassed, 0) as period_bypassed, 
               sc.completed_at, 
               sc.notes,
               u.full_name as completed_by
        FROM students s
        JOIN courses crs ON crs.id = s.course_id
        LEFT JOIN student_completion sc ON sc.student_id = s.id
        LEFT JOIN users u ON u.id = sc.completed_by
        ORDER BY s.full_name
    ")->fetchAll();
} catch (PDOException $e) {
    $tableError = 'student_completion table not found. Please run the installer first at: <a href="/Attachment/install.php">/Attachment/install.php</a>';
}

require __DIR__ . '/includes/header.php';
?>
require __DIR__ . '/includes/header.php';
?>
<?php if ($tableError): ?>
<div class="alert alert-error" style="margin:1rem;">
    <?= $tableError ?>
</div>
<?php else: ?>
<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Student Completion Management</h3>
    <p style="font-size:.9rem;margin-bottom:1rem;">Mark students as completed and optionally bypass the remaining attachment period requirement.</p>
    
    <table>
        <tr>
            <th>Reg No</th>
            <th>Name</th>
            <th>Course</th>
            <th>Placement Status</th>
            <th>Period End</th>
            <th>Avg Mark</th>
            <th>Completed</th>
            <th>Period Bypassed</th>
            <th></th>
        </tr>
        <?php foreach ($students as $s): ?>
        <tr>
            <td><?= e($s['reg_no']) ?></td>
            <td><?= e($s['full_name']) ?></td>
            <td><?= e($s['course']) ?></td>
            <td>
                <?php if ($s['placement_id']): ?>
                    <span class="badge badge-<?= $s['placement_status'] === 'approved' ? 'approved' : ($s['placement_status'] === 'rejected' ? 'danger' : 'pending') ?>">
                        <?= ucfirst($s['placement_status']) ?>
                    </span>
                <?php else: ?>
                    <span class="badge">No Placement</span>
                <?php endif; ?>
            </td>
            <td><?= e($s['end_date'] ?? 'N/A') ?></td>
            <td><?= $s['avg_mark'] ? (int) $s['avg_mark'] : 'N/A' ?></td>
            <td><?= $s['is_completed'] ? '<span style="color:green;">✓ Yes</span>' : 'No' ?></td>
            <td><?= $s['period_bypassed'] ? '<span style="color:green;">✓ Yes</span>' : 'No' ?></td>
            <td>
                <button class="btn btn-sm" onclick="document.getElementById('modal-<?= (int) $s['id'] ?>').style.display='block'">Edit</button>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$students): ?><tr><td colspan="9">No students found.</td></tr><?php endif; ?>
    </table>
</div>

<?php foreach ($students as $s): ?>
<div id="modal-<?= (int) $s['id'] ?>" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.4);">
    <div style="background-color:#fefefe;margin:10% auto;padding:2rem;border:1px solid #888;width:90%;max-width:500px;border-radius:5px;">
        <span onclick="document.getElementById('modal-<?= (int) $s['id'] ?>').style.display='none'" style="color:#aaa;float:right;font-size:28px;font-weight:bold;cursor:pointer;">&times;</span>
        <h4 style="margin-bottom:1rem;color:#1a5276;">Update Completion Status</h4>
        <p style="font-size:.95rem;margin-bottom:1rem;"><strong><?= e($s['full_name']) ?></strong> (<?= e($s['reg_no']) ?>)</p>
        
        <form method="post">
            <input type="hidden" name="action" value="update_completion">
            <input type="hidden" name="student_id" value="<?= (int) $s['id'] ?>">
            
            <div style="margin-bottom:1rem;">
                <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                    <input type="checkbox" name="is_completed" <?= $s['is_completed'] ? 'checked' : '' ?>>
                    <span>Mark as Completed</span>
                </label>
            </div>

            <div style="margin-bottom:1rem;">
                <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                    <input type="checkbox" name="period_bypassed" <?= $s['period_bypassed'] ? 'checked' : '' ?>>
                    <span>Bypass Remaining Period Requirement</span>
                </label>
            </div>

            <div style="margin-bottom:1rem;">
                <label>Notes</label>
                <textarea name="notes" style="width:100%;height:80px;padding:0.5rem;border:1px solid #ddd;border-radius:3px;"><?= e($s['notes'] ?? '') ?></textarea>
            </div>

            <div style="display:flex;gap:0.5rem;margin-bottom:1rem;">
                <button type="submit" class="btn">Save Changes</button>
                <?php if ($s['is_completed'] || $s['period_bypassed']): ?>
                <button type="button" class="btn btn-danger" onclick="if(confirm('Clear completion status?')) { document.querySelector('input[value=clear_completion]').value='clear_completion'; document.querySelector('form').action=''; this.form.submit(); } else return false;">Clear Status</button>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modal-<?= (int) $s['id'] ?>').style.display='none'">Cancel</button>
            </div>

            <?php if ($s['completed_at']): ?>
            <div style="font-size:0.85rem;color:#666;border-top:1px solid #ddd;padding-top:1rem;">
                Last updated by <?= e($s['completed_by']) ?> on <?= e($s['completed_at']) ?>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<script>
window.onclick = function(event) {
    if (event.target.id.startsWith('modal-') && event.target.style.display === 'block') {
        event.target.style.display = 'none';
    }
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
