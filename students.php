<?php
$pageTitle = 'Students';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['admin', 'coordinator']);

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add') {
            $stmt = $pdo->prepare('INSERT INTO students (reg_no, full_name, gender, course_id, department_id, phone, email, year_of_study) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([
                trim($_POST['reg_no']), trim($_POST['full_name']), $_POST['gender'],
                (int) $_POST['course_id'], (int) $_POST['department_id'],
                trim($_POST['phone'] ?? ''), trim($_POST['email'] ?? ''), trim($_POST['year_of_study']),
            ]);
            flash('success', 'Student added successfully.');
        } elseif ($action === 'edit') {
            $stmt = $pdo->prepare('UPDATE students SET full_name = ?, gender = ?, course_id = ?, department_id = ?, phone = ?, email = ?, year_of_study = ? WHERE id = ?');
            $stmt->execute([
                trim($_POST['full_name']), $_POST['gender'],
                (int) $_POST['course_id'], (int) $_POST['department_id'],
                trim($_POST['phone'] ?? ''), trim($_POST['email'] ?? ''), trim($_POST['year_of_study']),
                (int) $_POST['student_id']
            ]);
            flash('success', 'Student updated successfully.');
        } elseif ($action === 'delete' && isset($_POST['id'])) {
            $pdo->prepare('DELETE FROM students WHERE id = ?')->execute([(int) $_POST['id']]);
            flash('success', 'Student deleted.');
        }
    } catch (PDOException $e) {
        flash('error', 'Error: ' . $e->getMessage());
    }
    redirect('/Attachment/students.php');
}

$departments = $pdo->query('SELECT * FROM departments ORDER BY name')->fetchAll();
$students = $pdo->query('
    SELECT s.*, d.name as dept_name, c.name as course_name, c.course_type
    FROM students s 
    JOIN departments d ON d.id = s.department_id 
    JOIN courses c ON c.id = s.course_id 
    ORDER BY s.full_name
')->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Add Student</h3>
    <form method="post" class="form-grid" id="addStudentForm">
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
        <div>
            <label>Department</label>
            <select name="department_id" id="department_id" required onchange="updateCourses()">
                <option value="">Select Department</option>
                <?php foreach ($departments as $d): ?>
                <option value="<?= (int) $d['id'] ?>"><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Course</label>
            <select name="course_id" id="course_id" required onchange="updateYearOptions()">
                <option value="">Select Department First</option>
            </select>
        </div>
        <div>
            <label>Year of Study</label>
            <select name="year_of_study" id="year_of_study" required>
                <option value="">Select Course First</option>
            </select>
        </div>
        <div><label>Phone</label><input name="phone"></div>
        <div><label>Email</label><input type="email" name="email"></div>
        <div style="grid-column:1/-1"><button type="submit" class="btn">Save Student</button></div>
    </form>
</div>

<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Student List (<?= count($students) ?>)</h3>
    <table>
        <tr>
            <th>Reg No</th><th>Name</th><th>Department</th><th>Course</th><th>Year</th><th>Phone</th><th></th>
        </tr>
        <?php foreach ($students as $s): ?>
        <tr>
            <td><?= e($s['reg_no']) ?></td>
            <td><?= e($s['full_name']) ?></td>
            <td><?= e($s['dept_name']) ?></td>
            <td><?= e($s['course_name']) ?></td>
            <td><?= e($s['year_of_study']) ?></td>
            <td><?= e($s['phone']) ?></td>
            <td>
                <button class="btn btn-sm" onclick="document.getElementById('edit-student-<?= (int) $s['id'] ?>').style.display='block'">Edit</button>
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

<!-- Edit Student Modals -->
<?php foreach ($students as $s): ?>
<div id="edit-student-<?= (int) $s['id'] ?>" style="display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.4);">
    <div style="background-color:#fefefe;margin:5% auto;padding:2rem;border:1px solid #888;width:90%;max-width:600px;border-radius:5px;max-height:90vh;overflow-y:auto;">
        <span onclick="document.getElementById('edit-student-<?= (int) $s['id'] ?>').style.display='none'" style="color:#aaa;float:right;font-size:28px;font-weight:bold;cursor:pointer;">&times;</span>
        <h4 style="margin-bottom:1rem;color:#1a5276;">Edit Student</h4>
        <form method="post" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="student_id" value="<?= (int) $s['id'] ?>">
            
            <div><label>Registration No.</label><input value="<?= e($s['reg_no']) ?>" disabled style="background:#f0f0f0;"></div>
            <div><label>Full Name</label><input name="full_name" value="<?= e($s['full_name']) ?>" required></div>
            <div><label>Gender</label>
                <select name="gender" required>
                    <option value="Male" <?= $s['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $s['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                    <option value="Other" <?= $s['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            <div>
                <label>Department</label>
                <select name="department_id" id="edit-department_id-<?= (int) $s['id'] ?>" required onchange="updateEditCourses(<?= (int) $s['id'] ?>)">
                    <option value="">Select Department</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= (int) $d['id'] ?>" <?= $s['department_id'] == $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Course</label>
                <select name="course_id" id="edit-course_id-<?= (int) $s['id'] ?>" required onchange="updateEditYearOptions(<?= (int) $s['id'] ?>)">
                    <option value="<?= (int) $s['course_id'] ?>"><?= e($s['course_name']) ?></option>
                </select>
            </div>
            <div>
                <label>Year of Study</label>
                <select name="year_of_study" id="edit-year_of_study-<?= (int) $s['id'] ?>" required>
                    <option value="<?= e($s['year_of_study']) ?>"><?= e($s['year_of_study']) ?></option>
                </select>
            </div>
            <div><label>Phone</label><input name="phone" value="<?= e($s['phone']) ?>"></div>
            <div><label>Email</label><input type="email" name="email" value="<?= e($s['email']) ?>"></div>
            
            <div style="grid-column:1/-1;display:flex;gap:0.5rem;margin-top:1rem;">
                <button type="submit" class="btn">Save Changes</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('edit-student-<?= (int) $s['id'] ?>').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<script>
const coursesData = <?php 
$allCourses = $pdo->query('SELECT id, department_id, name, course_type FROM courses')->fetchAll();
$coursesByDept = [];
foreach ($allCourses as $c) {
    if (!isset($coursesByDept[$c['department_id']])) {
        $coursesByDept[$c['department_id']] = [];
    }
    $coursesByDept[$c['department_id']][] = $c;
}
echo json_encode($coursesByDept);
?>;

const yearOptions = {
    'CDACC': ['Module 1', 'Module 2', 'Module 3', 'Module 4'],
    'KNEC': ['Year 1', 'Year 2', 'Year 3']
};

function updateCourses() {
    const deptId = document.getElementById('department_id').value;
    const courseSelect = document.getElementById('course_id');
    courseSelect.innerHTML = '<option value="">Select Course</option>';
    
    if (deptId && coursesData[deptId]) {
        coursesData[deptId].forEach(course => {
            const option = document.createElement('option');
            option.value = course.id;
            option.textContent = course.name + ' (' + course.course_type + ')';
            courseSelect.appendChild(option);
        });
    }
    updateYearOptions();
}

function updateYearOptions() {
    const courseId = document.getElementById('course_id').value;
    const yearSelect = document.getElementById('year_of_study');
    yearSelect.innerHTML = '<option value="">Select Year</option>';
    
    if (courseId && coursesData) {
        const deptId = document.getElementById('department_id').value;
        if (coursesData[deptId]) {
            const course = coursesData[deptId].find(c => c.id == courseId);
            if (course && yearOptions[course.course_type]) {
                yearOptions[course.course_type].forEach(year => {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    yearSelect.appendChild(option);
                });
            }
        }
    }
}

function updateEditCourses(studentId) {
    const deptId = document.getElementById('edit-department_id-' + studentId).value;
    const courseSelect = document.getElementById('edit-course_id-' + studentId);
    courseSelect.innerHTML = '<option value="">Select Course</option>';
    
    if (deptId && coursesData[deptId]) {
        coursesData[deptId].forEach(course => {
            const option = document.createElement('option');
            option.value = course.id;
            option.textContent = course.name + ' (' + course.course_type + ')';
            courseSelect.appendChild(option);
        });
    }
    updateEditYearOptions(studentId);
}

function updateEditYearOptions(studentId) {
    const courseId = document.getElementById('edit-course_id-' + studentId).value;
    const yearSelect = document.getElementById('edit-year_of_study-' + studentId);
    yearSelect.innerHTML = '<option value="">Select Year</option>';
    
    if (courseId && coursesData) {
        const deptId = document.getElementById('edit-department_id-' + studentId).value;
        if (coursesData[deptId]) {
            const course = coursesData[deptId].find(c => c.id == courseId);
            if (course && yearOptions[course.course_type]) {
                yearOptions[course.course_type].forEach(year => {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    yearSelect.appendChild(option);
                });
            }
        }
    }
}

window.onclick = function(event) {
    if (event.target.id.startsWith('edit-student-') && event.target.style.display === 'block') {
        event.target.style.display = 'none';
    }
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
