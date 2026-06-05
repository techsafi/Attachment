<?php
$pageTitle = 'Assessors & Marks';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['admin', 'coordinator']);

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'assign') {
        $studentIds = $_POST['student_id'] ?? [];
        if (!is_array($studentIds)) {
            $studentIds = [$studentIds];
        }

        $stmt = $pdo->prepare('INSERT INTO assessor_assignments (student_id, assessor_id, assigned_by) VALUES (?,?,?)');
        $assignedCount = 0;
        $duplicateCount = 0;

        foreach ($studentIds as $studentId) {
            $studentId = (int) $studentId;
            if ($studentId <= 0) {
                continue;
            }
            try {
                $stmt->execute([$studentId, (int) $_POST['assessor_id'], current_user()['id']]);
                $assignedCount++;
            } catch (PDOException) {
                $duplicateCount++;
            }
        }

        if ($assignedCount > 0 && $duplicateCount === 0) {
            flash('success', sprintf('Assigned %d student(s) to the assessor.', $assignedCount));
        } elseif ($assignedCount > 0) {
            flash('success', sprintf('Assigned %d student(s), %d were already assigned.', $assignedCount, $duplicateCount));
        } else {
            flash('error', 'No new students were assigned. They may already be assigned to this assessor.');
        }
    } elseif ($action === 'unassign') {
        $pdo->prepare('DELETE FROM assessor_assignments WHERE id = ?')->execute([(int) $_POST['id']]);
        flash('success', 'Assignment removed.');
    }
    redirect('/Attachment/assessors.php');
}

$assessors = $pdo->query("SELECT id, full_name, username FROM users WHERE role = 'assessor' ORDER BY full_name")->fetchAll();
$students = $pdo->query("
    SELECT s.id, s.reg_no, s.full_name FROM students s
    JOIN placements p ON p.student_id = s.id AND p.status = 'approved'
    ORDER BY s.full_name
")->fetchAll();
$assignments = $pdo->query("
    SELECT aa.*, s.full_name AS student_name, s.reg_no, u.full_name AS assessor_name
    FROM assessor_assignments aa
    JOIN students s ON s.id = aa.student_id
    JOIN users u ON u.id = aa.assessor_id
    ORDER BY aa.assigned_at DESC
")->fetchAll();
$marks = $pdo->query("
    SELECT m.*, s.full_name AS student_name, u.full_name AS assessor_name
    FROM marks m
    JOIN students s ON s.id = m.student_id
    JOIN users u ON u.id = m.assessor_id
    ORDER BY m.submitted_at DESC
")->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Assign Assessor to Student(s)</h3>
    <form method="post" class="form-grid">
        <input type="hidden" name="action" value="assign">
        <div><label>Student(s) (approved placement)</label>
            <select name="student_id[]" multiple size="8" required>
                <?php foreach ($students as $s): ?>
                <option value="<?= (int) $s['id'] ?>"><?= e($s['reg_no'] . ' - ' . $s['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <p style="margin:0.5rem 0 0;font-size:0.9rem;color:#555;">Hold Ctrl (Windows/Linux) or Cmd (Mac) to select
                multiple students.</p>
        </div>
        <div><label>Assessor</label>
            <select name="assessor_id" required>
                <?php foreach ($assessors as $a): ?>
                <option value="<?= (int) $a['id'] ?>"><?= e($a['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><button type="submit" class="btn">Assign</button></div>
    </form>
    <?php if (!$assessors): ?>
    <p style="margin-top:.5rem;font-size:.85rem;">No assessor accounts. Create users with role "assessor" first.</p>
    <?php endif; ?>
</div>
<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Current Assignments</h3>
    <table>
        <tr>
            <th>Student</th>
            <th>Assessor</th>
            <th>Assigned</th>
            <th></th>
        </tr>
        <?php foreach ($assignments as $a): ?>
        <tr>
            <td><?= e($a['student_name']) ?> (<?= e($a['reg_no']) ?>)</td>
            <td><?= e($a['assessor_name']) ?></td>
            <td><?= e($a['assigned_at']) ?></td>
            <td>
                <form method="post">
                    <input type="hidden" name="action" value="unassign">
                    <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                    <button class="btn btn-danger btn-sm">Remove</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Submitted Marks</h3>
    <table>
        <tr>
            <th>Student</th>
            <th>Assessor</th>
            <th>Practical</th>
            <th>Logbook</th>
            <th>Attitude</th>
            <th>Total</th>
            <th>Grade</th>
        </tr>
        <?php foreach ($marks as $m): ?>
        <tr>
            <td><?= e($m['student_name']) ?></td>
            <td><?= e($m['assessor_name']) ?></td>
            <td><?= (int) $m['practical'] ?></td>
            <td><?= (int) $m['logbook'] ?></td>
            <td><?= (int) $m['attitude'] ?></td>
            <td><?= (int) $m['total'] ?></td>
            <td><strong><?= e($m['grade']) ?></strong></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$marks): ?><tr>
            <td colspan="7">No marks submitted yet.</td>
        </tr><?php endif; ?>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>