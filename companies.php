<?php
$pageTitle = 'Companies';
require_once __DIR__ . '/includes/auth.php';
require_login();
require_role(['admin', 'coordinator']);

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmt = $pdo->prepare('INSERT INTO companies (name, address, contact_person, phone, email) VALUES (?,?,?,?,?)');
        $stmt->execute([
            trim($_POST['name']), trim($_POST['address'] ?? ''),
            trim($_POST['contact_person'] ?? ''), trim($_POST['phone'] ?? ''), trim($_POST['email'] ?? ''),
        ]);
        flash('success', 'Company added.');
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM companies WHERE id = ?')->execute([(int) $_POST['id']]);
        flash('success', 'Company deleted.');
    }
    redirect('/Attachment/companies.php');
}

$companies = $pdo->query('SELECT * FROM companies ORDER BY name')->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="card">
    <h3 style="margin-bottom:1rem;color:#1a5276;">Add Company / Industry Partner</h3>
    <form method="post" class="form-grid">
        <input type="hidden" name="action" value="add">
        <div><label>Company Name</label><input name="name" required></div>
        <div><label>Contact Person</label><input name="contact_person"></div>
        <div><label>Phone</label><input name="phone"></div>
        <div><label>Email</label><input type="email" name="email"></div>
        <div style="grid-column:1/-1"><label>Address</label><textarea name="address"></textarea></div>
        <div><button type="submit" class="btn">Save Company</button></div>
    </form>
</div>
<div class="card">
    <table>
        <tr><th>Name</th><th>Contact</th><th>Phone</th><th>Address</th><th></th></tr>
        <?php foreach ($companies as $c): ?>
        <tr>
            <td><?= e($c['name']) ?></td>
            <td><?= e($c['contact_person']) ?></td>
            <td><?= e($c['phone']) ?></td>
            <td><?= e($c['address']) ?></td>
            <td>
                <form method="post" onsubmit="return confirm('Delete?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                    <button class="btn btn-danger btn-sm">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
