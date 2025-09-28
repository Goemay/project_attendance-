<?php
// Simple admin creation helper. Protected by CSRF and should be removed after use in production.
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($password) >= 6) {
            $pdo = get_pdo();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,"admin")');
            try { $stmt->execute([$name,$email,$hash]); $success = 'Admin created.'; } catch (Exception $e) { $error = 'Failed: maybe email exists.'; }
        } else { $error = 'Invalid input.'; }
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<h2>Create Admin</h2>
<?php if(!empty($error)) echo '<p style="color:red">'.htmlspecialchars($error).'</p>'; ?>
<?php if(!empty($success)) echo '<p style="color:green">'.htmlspecialchars($success).'</p>'; ?>
<form method="post">
    <input type="hidden" name="csrf" value="<?=htmlspecialchars(csrf_token())?>">
    <label>Name<br><input name="name" required></label>
    <label>Email<br><input name="email" type="email" required></label>
    <label>Password<br><input name="password" type="password" required></label>
    <button type="submit">Create Admin</button>
</form>
<p>Note: Remove this file after use in production.</p>
<?php include __DIR__ . '/includes/footer.php'; ?>
