<?php
// login.php (Tailwind UI)
require_once __DIR__ . '/db.php';
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        try {
            $pdo  = get_pdo();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = (int)$user['id'];      // required by rest of app
                $_SESSION['user_name'] = $user['name'] ?? 'User';
                $_SESSION['user_email']= $user['email'] ?? $email;
                header("Location: index.php");
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (Throwable $e) {
            $error = 'Login failed, please try again.';
        }
    }
}
?>
<main class="min-h-screen bg-slate-50 flex items-center justify-center py-10">
  <div class="w-full max-w-md">
    <div class="bg-white shadow-xl rounded-2xl p-8 border border-slate-200">
      <div class="mb-6 text-center">
        <h1 class="text-2xl font-semibold text-slate-900">Sign in</h1>
        <p class="text-slate-500 text-sm mt-1">Access the attendance dashboard</p>
      </div>

      <?php if ($error): ?>
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-rose-900 text-sm">
          <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <form method="post" class="space-y-4">
        <div>
          <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
          <input id="email" name="email" type="email" required
                 class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                 placeholder="name@example.com">
        </div>
        <div>
          <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
          <input id="password" name="password" type="password" required
                 class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                 placeholder="••••••••">
        </div>

        <button type="submit"
                class="w-full inline-flex justify-center items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-white font-semibold shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600">
          Sign in
        </button>
      </form>

      <p class="mt-6 text-center text-sm text-slate-600">
        Don’t have an account?
        <a href="register.php" class="font-medium text-indigo-600 hover:text-indigo-500">Register</a>
      </p>
    </div>
  </div>
</main>

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Attendance System</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<?php include __DIR__ . '/includes/footer.php'; ?>
