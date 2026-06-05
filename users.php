<?php
$pageTitle = 'Users';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['admin']);

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $role = $_POST['role'];
        $studentId = $role === 'student' ? (int) ($_POST['student_id'] ?: 0) : null;
        if ($role === 'student' && !$studentId) {
            flash('error', 'Select a student record for student login.');
        } else {
            $username = trim($_POST['username'] ?? '');
            $fullName = trim($_POST['full_name'] ?? '');

            // For student accounts, default username to admission number (reg_no)
            if ($role === 'student') {
                $s = $pdo->prepare('SELECT reg_no, full_name FROM students WHERE id = ?');
                $s->execute([$studentId]);
                $student = $s->fetch();
                if ($student) {
                    if ($username === '') $username = $student['reg_no'];
                    if ($fullName === '') $fullName = $student['full_name'];
                }
            }

            $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username, password, full_name, role, student_id) VALUES (?,?,?,?,?)');
            $stmt->execute([$username, $hash, $fullName, $role, $studentId ?: null]);
            flash('success', 'User created.');
        }
    } elseif ($action === 'delete') {
        $id = (int) $_POST['id'];
        if ($id !== current_user()['id']) {
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
            flash('success', 'User deleted.');
        }
    }
    redirect('/Attachment/users.php');
}

$users = $pdo->query("
    SELECT u.*, s.reg_no, s.full_name AS linked_student
    FROM users u
    LEFT JOIN students s ON s.id = u.student_id
    ORDER BY u.role, u.full_name
")->fetchAll();
$students = $pdo->query('SELECT id, reg_no, full_name FROM students ORDER BY full_name')->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Create User Account</h3>
    <form method="post" class="form-grid" id="userForm">
        <input type="hidden" name="action" value="add">
        <div>
            <label>Username (students can use admission number)</label>
            <input name="username" placeholder="For students, leave blank to use admission number">
        </div>
        <div><label>Password</label><input type="password" name="password" required minlength="4"></div>
        <div>
            <label>Full Name</label>
            <input name="full_name" placeholder="For students, leave blank to use student name">
        </div>
        <div><label>Role</label>
            <select name="role" id="roleSelect" required onchange="document.getElementById('studentLink').style.display=this.value==='student'?'block':'none'">
                <option value="coordinator">Coordinator</option>
                <option value="assessor">Assessor</option>
                <option value="student">Student</option>
            </select>
        </div>
        <div id="studentLink" style="display:none">
            <label>Link to Student Record</label>
            <select name="student_id">
                <option value="">-- Select --</option>
                <?php foreach ($students as $s): ?>
                    <option value="<?= (int) $s['id'] ?>"><?= e($s['reg_no'] . ' - ' . $s['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div><button type="submit" class="btn">Create User</button></div>
    </form>
</div>
<div class="card">
    <table>
        <tr><th>Username</th><th>Name</th><th>Role</th><th>Linked Student</th><th></th></tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= e($u['username']) ?></td>
            <td><?= e($u['full_name']) ?></td>
            <td><?= e(ucfirst($u['role'])) ?></td>
            <td><?= e($u['linked_student'] ?? '-') ?></td>
            <td>
                <?php if ((int) $u['id'] !== current_user()['id']): ?>
                <form method="post" onsubmit="return confirm('Delete user?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                    <button class="btn btn-danger btn-sm">Delete</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
