<?php
$pageTitle = 'Upload Documents';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['student']);

$studentId = current_user()['student_id'] ?? null;
if (!$studentId) {
    flash('error', 'Your login is not linked to a student record.');
    redirect('/Attachment/dashboard.php');
}

$pdo = db();
$allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

function ini_size_to_bytes(string $val): int
{
    $unit = strtolower(substr($val, -1));
    $bytes = (int) $val;
    if ($unit === 'g') return $bytes * 1024 * 1024 * 1024;
    if ($unit === 'm') return $bytes * 1024 * 1024;
    if ($unit === 'k') return $bytes * 1024;
    return $bytes;
}

$phpMaxSize = min(
    ini_size_to_bytes(ini_get('upload_max_filesize') ?: '2M'),
    ini_size_to_bytes(ini_get('post_max_size') ?: '8M')
);
$maxSize = min(5 * 1024 * 1024, $phpMaxSize);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['submission_type'] ?? '';
    if (!in_array($type, ['logbook', 'recommendation'], true)) {
        flash('error', 'Invalid submission type.');
    } elseif (empty($_FILES['file']['name'])) {
        flash('error', 'Please select a file.');
    } else {
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            flash('error', 'Allowed formats: PDF, DOC, DOCX, JPG, PNG.');
        } elseif ($_FILES['file']['size'] > $maxSize) {
            flash('error', 'File too large (max ' . round($maxSize / 1024 / 1024, 1) . 'MB).');
        } elseif ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the server limit.',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the maximum size allowed by the form.',
                UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'Upload stopped by extension.',
            ];
            flash('error', $uploadErrors[$_FILES['file']['error']] ?? 'Upload failed.');
        } else {
            $folder = $type === 'logbook' ? 'logbooks' : 'recommendations';
            $dir = __DIR__ . '/uploads/' . $folder;
            if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
                flash('error', 'Unable to create upload folder.');
            } elseif (!is_writable($dir) && !chmod($dir, 0777)) {
                flash('error', 'Upload folder is not writable.');
            } else {
                $stored = $studentId . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['file']['tmp_name'], $dir . '/' . $stored)) {
                    $stmt = $pdo->prepare('INSERT INTO submissions (student_id, submission_type, file_name, original_name) VALUES (?,?,?,?)');
                    $stmt->execute([$studentId, $type, $folder . '/' . $stored, $_FILES['file']['name']]);
                    flash('success', ucfirst($type) . ' uploaded successfully.');
                } else {
                    flash('error', 'Could not save file; check upload directory permissions.');
                }
            }
        }
    }
    redirect('/Attachment/upload.php');
}

$mine = $pdo->prepare('SELECT * FROM submissions WHERE student_id = ? ORDER BY submitted_at DESC');
$mine->execute([$studentId]);
$submissions = $mine->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Upload Logbook or Recommendation Letter</h3>
    <form method="post" enctype="multipart/form-data" class="form-grid">
        <div><label>Document Type</label>
            <select name="submission_type" required>
                <option value="logbook">Logbook</option>
                <option value="recommendation">Recommendation Letter</option>
            </select>
        </div>
        <div><label>File (PDF, Word, or Image — max 5MB)</label><input type="file" name="file" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"></div>
        <div><button type="submit" class="btn">Upload</button></div>
    </form>
</div>
<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">My Uploads</h3>
    <table>
        <tr><th>Type</th><th>File</th><th>Date</th><th>Status</th></tr>
        <?php foreach ($submissions as $sub): ?>
        <tr>
            <td><?= e(ucfirst($sub['submission_type'])) ?></td>
            <td><?= e($sub['original_name']) ?></td>
            <td><?= e($sub['submitted_at']) ?></td>
            <td><?= e(ucfirst($sub['status'])) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
