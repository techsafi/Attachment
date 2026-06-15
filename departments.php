<?php
$pageTitle = 'Departments & Courses';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['admin', 'coordinator']);

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Department actions
    if ($action === 'add_department') {
        $stmt = $pdo->prepare('INSERT INTO departments (name, description) VALUES (?, ?)');
        $stmt->execute([trim($_POST['dept_name']), trim($_POST['dept_desc'] ?? '')]);
        flash('success', 'Department added successfully.');
    } elseif ($action === 'edit_department') {
        $stmt = $pdo->prepare('UPDATE departments SET name = ?, description = ? WHERE id = ?');
        $stmt->execute([trim($_POST['dept_name']), trim($_POST['dept_desc'] ?? ''), (int) $_POST['dept_id']]);
        flash('success', 'Department updated successfully.');
    } elseif ($action === 'delete_department' && isset($_POST['dept_id'])) {
        try {
            // First delete all courses in this department
            $pdo->prepare('DELETE FROM courses WHERE department_id = ?')->execute([(int) $_POST['dept_id']]);
            // Then delete the department
            $pdo->prepare('DELETE FROM departments WHERE id = ?')->execute([(int) $_POST['dept_id']]);
            flash('success', 'Department and its courses deleted.');
        } catch (PDOException $e) {
            flash('error', 'Cannot delete department: ' . $e->getMessage());
        }
    }

    // Course actions
    elseif ($action === 'add_course') {
        $stmt = $pdo->prepare('INSERT INTO courses (department_id, name, course_type) VALUES (?, ?, ?)');
        $stmt->execute([(int) $_POST['dept_id'], trim($_POST['course_name']), $_POST['course_type']]);
        flash('success', 'Course added successfully.');
    } elseif ($action === 'edit_course') {
        $stmt = $pdo->prepare('UPDATE courses SET name = ?, course_type = ? WHERE id = ?');
        $stmt->execute([trim($_POST['course_name']), $_POST['course_type'], (int) $_POST['course_id']]);
        flash('success', 'Course updated successfully.');
    } elseif ($action === 'delete_course' && isset($_POST['course_id'])) {
        try {
            $pdo->prepare('DELETE FROM courses WHERE id = ?')->execute([(int) $_POST['course_id']]);
            flash('success', 'Course deleted.');
        } catch (PDOException $e) {
            flash('error', 'Cannot delete course: Students are enrolled in this course.');
        }
    }

    redirect('/Attachment/departments.php');
}

$departments = $pdo->query('SELECT * FROM departments ORDER BY name')->fetchAll();
$courses = $pdo->query('
    SELECT c.*, d.name as dept_name 
    FROM courses c 
    JOIN departments d ON d.id = c.department_id 
    ORDER BY d.name, c.name
')->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
    <div class="card">
        <h3 style="margin-bottom:1rem;color:#1a5276;">Add Department</h3>
        <form method="post" class="form-grid">
            <input type="hidden" name="action" value="add_department">
            <div><label>Department Name</label><input name="dept_name" required></div>
            <div><label>Description</label><input name="dept_desc" placeholder="Optional"></div>
            <div style="grid-column:1/-1"><button type="submit" class="btn">Add Department</button></div>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-bottom:1rem;color:#1a5276;">Add Course</h3>
        <form method="post" class="form-grid">
            <input type="hidden" name="action" value="add_course">
            <div>
                <label>Department</label>
                <select name="dept_id" required>
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= (int) $d['id'] ?>"><?= e($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Course Name</label><input name="course_name" required></div>
            <div>
                <label>Course Type</label>
                <select name="course_type" required>
                    <option value="">Select Type</option>
                    <option value="CDACC">CDACC</option>
                    <option value="KNEC">KNEC</option>
                </select>
            </div>
            <div style="grid-column:1/-1"><button type="submit" class="btn">Add Course</button></div>
        </form>
    </div>
</div>

<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Departments (<?= count($departments) ?>)</h3>
    <table>
        <tr><th>Name</th><th>Description</th><th></th></tr>
        <?php foreach ($departments as $d): ?>
        <tr>
            <td><?= e($d['name']) ?></td>
            <td><?= e($d['description'] ?? '') ?></td>
            <td>
                <button class="btn btn-sm" onclick="document.getElementById('edit-dept-<?= (int) $d['id'] ?>').style.display='block'">Edit</button>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete department and all its courses?');">
                    <input type="hidden" name="action" value="delete_department">
                    <input type="hidden" name="dept_id" value="<?= (int) $d['id'] ?>">
                    <button class="btn btn-danger btn-sm">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$departments): ?><tr><td colspan="3">No departments yet.</td></tr><?php endif; ?>
    </table>
</div>

<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Courses (<?= count($courses) ?>)</h3>
    <table>
        <tr><th>Department</th><th>Course Name</th><th>Type</th><th></th></tr>
        <?php foreach ($courses as $c): ?>
        <tr>
            <td><?= e($c['dept_name']) ?></td>
            <td><?= e($c['name']) ?></td>
            <td><span class="badge"><?= e($c['course_type']) ?></span></td>
            <td>
                <button class="btn btn-sm" onclick="document.getElementById('edit-course-<?= (int) $c['id'] ?>').style.display='block'">Edit</button>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete this course?');">
                    <input type="hidden" name="action" value="delete_course">
                    <input type="hidden" name="course_id" value="<?= (int) $c['id'] ?>">
                    <button class="btn btn-danger btn-sm">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$courses): ?><tr><td colspan="4">No courses yet.</td></tr><?php endif; ?>
    </table>
</div>

<!-- Edit Department Modals -->
<?php foreach ($departments as $d): ?>
<div id="edit-dept-<?= (int) $d['id'] ?>" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.4);">
    <div style="background-color:#fefefe;margin:10% auto;padding:2rem;border:1px solid #888;width:90%;max-width:500px;border-radius:5px;">
        <span onclick="document.getElementById('edit-dept-<?= (int) $d['id'] ?>').style.display='none'" style="color:#aaa;float:right;font-size:28px;font-weight:bold;cursor:pointer;">&times;</span>
        <h4 style="margin-bottom:1rem;color:#1a5276;">Edit Department</h4>
        <form method="post">
            <input type="hidden" name="action" value="edit_department">
            <input type="hidden" name="dept_id" value="<?= (int) $d['id'] ?>">
            <div style="margin-bottom:1rem;">
                <label>Department Name</label>
                <input name="dept_name" value="<?= e($d['name']) ?>" required>
            </div>
            <div style="margin-bottom:1rem;">
                <label>Description</label>
                <input name="dept_desc" value="<?= e($d['description'] ?? '') ?>">
            </div>
            <div style="display:flex;gap:0.5rem;">
                <button type="submit" class="btn">Save Changes</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('edit-dept-<?= (int) $d['id'] ?>').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<!-- Edit Course Modals -->
<?php foreach ($courses as $c): ?>
<div id="edit-course-<?= (int) $c['id'] ?>" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.4);">
    <div style="background-color:#fefefe;margin:10% auto;padding:2rem;border:1px solid #888;width:90%;max-width:500px;border-radius:5px;">
        <span onclick="document.getElementById('edit-course-<?= (int) $c['id'] ?>').style.display='none'" style="color:#aaa;float:right;font-size:28px;font-weight:bold;cursor:pointer;">&times;</span>
        <h4 style="margin-bottom:1rem;color:#1a5276;">Edit Course</h4>
        <form method="post">
            <input type="hidden" name="action" value="edit_course">
            <input type="hidden" name="course_id" value="<?= (int) $c['id'] ?>">
            <div style="margin-bottom:1rem;">
                <label>Department</label>
                <input type="text" value="<?= e($c['dept_name']) ?>" disabled style="background:#f0f0f0;">
            </div>
            <div style="margin-bottom:1rem;">
                <label>Course Name</label>
                <input name="course_name" value="<?= e($c['name']) ?>" required>
            </div>
            <div style="margin-bottom:1rem;">
                <label>Course Type</label>
                <select name="course_type" required>
                    <option value="CDACC" <?= $c['course_type'] === 'CDACC' ? 'selected' : '' ?>>CDACC</option>
                    <option value="KNEC" <?= $c['course_type'] === 'KNEC' ? 'selected' : '' ?>>KNEC</option>
                </select>
            </div>
            <div style="display:flex;gap:0.5rem;">
                <button type="submit" class="btn">Save Changes</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('edit-course-<?= (int) $c['id'] ?>').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<script>
window.onclick = function(event) {
    if ((event.target.id.startsWith('edit-dept-') || event.target.id.startsWith('edit-course-')) && event.target.style.display === 'block') {
        event.target.style.display = 'none';
    }
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
