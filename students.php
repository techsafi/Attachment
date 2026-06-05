<?php
$pageTitle = 'Students';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['admin', 'coordinator']);

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmt = $pdo->prepare('INSERT INTO students (reg_no, full_name, gender, course, department, phone, email, year_of_study) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([
            trim($_POST['reg_no']), trim($_POST['full_name']), $_POST['gender'],
            trim($_POST['course']), trim($_POST['department']),
            trim($_POST['phone'] ?? ''), trim($_POST['email'] ?? ''), trim($_POST['year_of_study']),
        ]);
        flash('success', 'Student added successfully.');
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        $pdo->prepare('DELETE FROM students WHERE id = ?')->execute([(int) $_POST['id']]);
        flash('success', 'Student deleted.');
    }
    redirect('/Attachment/students.php');
}

$students = $pdo->query('SELECT * FROM students ORDER BY full_name')->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Add Student</h3>
    <form method="post" class="form-grid">
        <input type="hidden" name="action" value="add">
        <div><label>Registration No.</label><input name="reg_no" required></div>
        <div><label>Full Name</label><input name="full_name" required></div>
        <div><label>Gender</label>
            <select name="gender" required>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div><label>Course</label><input name="course" required placeholder="e.g. Electrical Installation"></div>
        <div><label>Department</label><input name="department" required></div>
        <div><label>Year of Study</label><input name="year_of_study" required placeholder="e.g. Year 3"></div>
        <div><label>Phone</label><input name="phone"></div>
        <div><label>Email</label><input type="email" name="email"></div>
        <div style="grid-column:1/-1"><button type="submit" class="btn">Save Student</button></div>
    </form>
</div>
<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Student List (<?= count($students) ?>)</h3>
    <table>
        <tr>
            <th>Reg No</th><th>Name</th><th>Course</th><th>Department</th><th>Year</th><th>Phone</th><th></th>
        </tr>
        <?php foreach ($students as $s): ?>
        <tr>
            <td><?= e($s['reg_no']) ?></td>
            <td><?= e($s['full_name']) ?></td>
            <td><?= e($s['course']) ?></td>
            <td><?= e($s['department']) ?></td>
            <td><?= e($s['year_of_study']) ?></td>
            <td><?= e($s['phone']) ?></td>
            <td>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete this student?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                    <button class="btn btn-danger btn-sm">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$students): ?><tr><td colspan="7">No students yet.</td></tr><?php endif; ?>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
