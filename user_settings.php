<?php
// user_settings.php — compact layout so footer stays visible

require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/db.php';
date_default_timezone_set('Asia/Jakarta');

$pdo = get_pdo();
$u   = current_user();
$userId    = (int)$u['id'];
$userName  = $u['name']  ?? '';
$userEmail = $u['email'] ?? '';

$ok = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($token)) {
        $err = 'Invalid request token.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $pass = $_POST['password'] ?? '';
        $conf = $_POST['password_confirm'] ?? '';

        if ($name === '') {
            $err = 'Name is required.';
        } elseif ($pass !== '' && $pass !== $conf) {
            $err = 'Password confirmation does not match.';
        } else {
            try {
                if ($pass !== '') {
                    $hash = password_hash($pass, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare('UPDATE users SET name = ?, password = ? WHERE id = ?');
                    $stmt->execute([$name, $hash, $userId]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET name = ? WHERE id = ?');
                    $stmt->execute([$name, $userId]);
                }
                $_SESSION['user_name'] = $name;
                $ok = 'Profile updated successfully.';
                $u  = current_user();
                $userName = $u['name'] ?? $name;
            } catch (Throwable $e) {
                $err = 'Update failed, please try again.';
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
$me = function_exists('current_user') ? current_user() : null;
$isAdmin = (($me['role'] ?? '') === 'admin');
?>
<?php include __DIR__ . '/Universal_button.php'; ?>
    <!-- Messages (tight margin) -->
    <?php if ($ok): ?>
      <div class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-900 text-sm"><?= htmlspecialchars($ok, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($err): ?>
      <div class="mb-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-rose-900 text-sm"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <!-- Card (reduced top space, no vertical centering) -->
    <div class="bg-white shadow-xl rounded-2xl p-6 sm:p-7 border border-slate-200">
      <h1 class="text-xl font-semibold text-slate-900">Account settings</h1>
      <p class="text-slate-500 text-sm mt-1">Update profile details</p>

      <form method="post" class="mt-5 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

        <div>
          <label for="name" class="block text-sm font-medium text-slate-700">Full name</label>
          <input id="name" name="name" type="text" required
                 class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                 value="<?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div>
          <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
          <input id="email" type="email"
                 class="mt-1 block w-full rounded-lg border-slate-200 bg-slate-50 text-slate-500 shadow-sm" value="<?= htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') ?>" disabled>
          <p class="text-xs text-slate-500 mt-1">Email changes are disabled.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="password" class="block text-sm font-medium text-slate-700">New password</label>
            <input id="password" name="password" type="password"
                   class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                   placeholder="Leave blank to keep current">
          </div>
          <div>
            <label for="password_confirm" class="block text-sm font-medium text-slate-700">Confirm password</label>
            <input id="password_confirm" name="password_confirm" type="password"
                   class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                   placeholder="Repeat new password">
          </div>
        </div>

        <div class="pt-1 flex items-center gap-3">
          <button type="submit"
                  class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-white font-semibold shadow-sm hover:bg-indigo-500">
            Save changes
          </button>
          <a href="index.php" class="text-sm text-slate-600 hover:text-slate-900">Back to dashboard</a>
        </div>
      </form>
    </div>

    <!-- small bottom spacer so footer doesn’t touch card but remains visible -->
    <div class="h-4"></div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
