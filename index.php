<?php
require_once __DIR__ . '/auth.php'; // has session + helpers
require_login();                     // force login
require_once __DIR__ . '/db.php';
date_default_timezone_set('Asia/Jakarta');

$pdo = get_pdo();
$u   = current_user();               // id, name, email, role
$userId   = (int)($u['id'] ?? 0);
$userName = htmlspecialchars($u['name'] ?? 'User', ENT_QUOTES, 'UTF-8');
$isAdmin  = ($u['role'] ?? 'user') === 'admin';

// Build today's attendance banner
$banner = ['class' => 'bg-sky-50 text-sky-900 border-sky-200', 'text' => 'Welcome to the dashboard.'];
$stmt = $pdo->prepare("
  SELECT type, `timestamp`, note
  FROM attendance
  WHERE user_id = ? AND DATE(`timestamp`) = CURDATE()
  ORDER BY `timestamp` DESC
  LIMIT 1
");
$stmt->execute([$userId]);
$last = $stmt->fetch();
if ($last && $last['type'] === 'checkin') {
  $t    = (new DateTime($last['timestamp']))->format('H:i:s');
  $note = htmlspecialchars($last['note'] ?? '', ENT_QUOTES, 'UTF-8');
  $banner = ['class' => 'bg-green-50 text-green-900 border-green-200', 'text' => "Already checked in at {$t} ({$note})."];
} else {
  $banner = ['class' => 'bg-amber-50 text-amber-900 border-amber-200', 'text' => 'Not yet checked in today.'];
}

include __DIR__ . '/includes/header.php'; // Tailwind loaded here
?>
<main class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-6">
  <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Dashboard</h1>
      <p class="text-slate-500">Welcome, <?= $userName ?></p>
    </div>
    <div class="flex items-center gap-2">
      <a href="attendance.php" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-white font-medium shadow-sm hover:bg-indigo-500">Open Attendance</a>
    </div>
  </div>

  <div class="mt-4 border rounded-xl px-4 py-3 flex items-center gap-3 shadow-sm <?= $banner['class'] ?>">
    <svg class="h-5 w-5 opacity-80" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-.75-11a.75.75 0 011.5 0v4a.75.75 0 01-1.5 0V7zm.75 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
    <p class="text-sm font-medium"><?= $banner['text'] ?></p>
  </div>

  <section class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <a href="attendance.php" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition">
      <div class="flex items-start justify-between">
        <div>
          <h3 class="text-base font-semibold text-slate-900">Attendance</h3>
          <p class="mt-1 text-sm text-slate-500">Mark and review check in/out.</p>
        </div>
        <span class="text-slate-300 group-hover:text-indigo-400 transition text-2xl leading-none">›</span>
      </div>
    </a>

    <a href="reports.php" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition">
      <div class="flex items-start justify-between">
        <div>
          <h3 class="text-base font-semibold text-slate-900">Reports<?= $isAdmin ? '' : ' (Mine)' ?></h3>
          <p class="mt-1 text-sm text-slate-500"><?= $isAdmin ? 'View all users reports.' : 'View personal report only.' ?></p>
        </div>
        <span class="text-slate-300 group-hover:text-indigo-400 transition text-2xl leading-none">›</span>
      </div>
    </a>

    <a href="late_summary.php" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition">
      <div class="flex items-start justify-between">
        <div>
          <h3 class="text-base font-semibold text-slate-900">Late tracker<?= $isAdmin ? '' : ' (Mine)' ?></h3>
          <p class="mt-1 text-sm text-slate-500"><?= $isAdmin ? 'Track lateness for all users.' : 'See personal lateness status.' ?></p>
        </div>
        <span class="text-slate-300 group-hover:text-indigo-400 transition text-2xl leading-none">›</span>
      </div>
    </a>
<?php if ($isAdmin): ?>
  <a href="admin.php" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition">
    <div class="flex items-start justify-between">
      <div>
        <h3 class="text-base font-semibold text-slate-900">Admin</h3>
        <p class="mt-1 text-sm text-slate-500">Open the admin panel to manage users and settings.</p>
      </div>
      <span class="text-slate-300 group-hover:text-indigo-400 transition text-2xl leading-none">›</span>
    </div>
  </a>
<?php endif; ?>
    <a href="user_settings.php" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition">
      <div class="flex items-start justify-between">
        <div>
          <h3 class="text-base font-semibold text-slate-900">Settings</h3>
          <p class="mt-1 text-sm text-slate-500">Edit name and password.</p>
        </div>
        <span class="text-slate-300 group-hover:text-indigo-400 transition text-2xl leading-none">›</span>
      </div>
    </a>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
