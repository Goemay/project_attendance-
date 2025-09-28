<?php
// admin.php (Tailwind modern UI)

require_once __DIR__ . '/auth.php';
require_admin(); // block non-admins
require_once __DIR__ . '/db.php';

date_default_timezone_set('Asia/Jakarta');
$u = current_user();
$userName = htmlspecialchars($u['name'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
?>
<?php include __DIR__ . '/includes/header.php'; // loads Tailwind via header ?>
<?php
// put right after: include __DIR__ . '/includes/header.php';
$me = function_exists('current_user') ? current_user() : null;
$isAdmin = (($me['role'] ?? '') === 'admin');
?>
<div class="mb-4 flex items-center gap-2">
  <button type="button"
          onclick="if (document.referrer) { history.back(); } else { window.location.href='<?= $isAdmin ? 'admin.php' : 'index.php' ?>'; }"
          class="inline-flex items-center rounded-lg bg-white border border-slate-200 px-3 py-2 text-slate-700 hover:bg-slate-50 shadow-sm">
    ← Back
  </button>
  <a href="index.php"
     class="inline-flex items-center rounded-lg bg-slate-900 px-3.5 py-2 text-white font-medium shadow-sm hover:bg-slate-800">
    Dashboard
  </a>
  <?php if ($isAdmin): ?>
    <a href="admin.php"
       class="inline-flex items-center rounded-lg bg-indigo-600 px-3.5 py-2 text-white font-medium shadow-sm hover:bg-indigo-500">
      Admin
    </a>
  <?php endif; ?>
</div>

<main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
  <!-- Top bar -->
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Admin Panel</h1>
      <p class="text-slate-500">Welcome, <?= $userName ?>. Use the dashboard to manage attendance, summaries, users, and settings.</p>
    </div>
    <div class="flex items-center gap-2">
      <a href="index.php" class="inline-flex items-center rounded-lg bg-white border border-slate-200 px-3 py-2 text-slate-700 hover:bg-slate-50 shadow-sm">
        ← Back to site
      </a>

    </div>
  </div>

  <!-- Layout: sidebar + content -->
  <div class="mt-6 grid grid-cols-1 lg:grid-cols-[240px_1fr] gap-6">
    <!-- Sidebar -->
    <aside class="lg:sticky lg:top-4">
      <nav class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <ul class="space-y-1">
          <li>
            <a href="reports.php"
               class="flex items-center gap-2 rounded-lg px-3 py-2 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 transition">
              <span class="inline-block h-2 w-2 rounded-full bg-indigo-400"></span>
              <span>Attendance Report</span>
            </a>
          </li>
          <li>
            <a href="late_summary.php"
               class="flex items-center gap-2 rounded-lg px-3 py-2 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 transition">
              <span class="inline-block h-2 w-2 rounded-full bg-purple-400"></span>
              <span>Latest Summary</span>
            </a>
          </li>
          <li>
            <a href="settings_admin.php"
               class="flex items-center gap-2 rounded-lg px-3 py-2 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 transition">
              <span class="inline-block h-2 w-2 rounded-full bg-amber-400"></span>
              <span>Attendance Settings</span>
            </a>
          </li>
          <li>
            <a href="manage_user.php?section=users"
               class="flex items-center gap-2 rounded-lg px-3 py-2 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 transition">
              <span class="inline-block h-2 w-2 rounded-full bg-emerald-400"></span>
              <span>Manage Users</span>
            </a>
          </li>
        </ul>
      </nav>
    </aside>

    <!-- Content -->
    <section>
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <!-- Card: Attendance Report -->
        <a href="reports.php"
           class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition">
          <div class="flex items-start justify-between">
            <div>
              <div class="inline-flex items-center gap-2 rounded-md bg-indigo-50 px-2 py-1 text-indigo-700 text-xs font-medium">
                <span class="h-2 w-2 rounded-full bg-indigo-500"></span> Reports
              </div>
              <h3 class="mt-2 text-base font-semibold text-slate-900">Attendance Report</h3>
              <p class="mt-1 text-sm text-slate-500">View and analyze attendance logs.</p>
            </div>
            <span class="text-slate-300 group-hover:text-indigo-400 transition text-2xl leading-none">›</span>
          </div>
        </a>

        <!-- Card: Latest Summary -->
        <a href="late_summary.php"
           class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition">
          <div class="flex items-start justify-between">
            <div>
              <div class="inline-flex items-center gap-2 rounded-md bg-purple-50 px-2 py-1 text-purple-700 text-xs font-medium">
                <span class="h-2 w-2 rounded-full bg-purple-500"></span> Summary
              </div>
              <h3 class="mt-2 text-base font-semibold text-slate-900">Latest Summary</h3>
              <p class="mt-1 text-sm text-slate-500">Review recent check-ins and check-outs.</p>
            </div>
            <span class="text-slate-300 group-hover:text-indigo-400 transition text-2xl leading-none">›</span>
          </div>
        </a>

        <!-- Card: Manage Users -->
        <a href="manage_user.php"
           class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition">
          <div class="flex items-start justify-between">
            <div>
              <div class="inline-flex items-center gap-2 rounded-md bg-emerald-50 px-2 py-1 text-emerald-700 text-xs font-medium">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Users
              </div>
              <h3 class="mt-2 text-base font-semibold text-slate-900">Manage Users</h3>
              <p class="mt-1 text-sm text-slate-500">Add, update, or remove users.</p>
            </div>
            <span class="text-slate-300 group-hover:text-indigo-400 transition text-2xl leading-none">›</span>
          </div>
        </a>

        <!-- Card: Attendance Settings -->
        <a href="settings_admin.php"
           class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition">
          <div class="flex items-start justify-between">
            <div>
              <div class="inline-flex items-center gap-2 rounded-md bg-amber-50 px-2 py-1 text-amber-700 text-xs font-medium">
                <span class="h-2 w-2 rounded-full bg-amber-500"></span> Settings
              </div>
              <h3 class="mt-2 text-base font-semibold text-slate-900">Attendance Settings</h3>
              <p class="mt-1 text-sm text-slate-500">Configure allowed location & radius.</p>
            </div>
            <span class="text-slate-300 group-hover:text-indigo-400 transition text-2xl leading-none">›</span>
          </div>
        </a>
      </div>
    </section>
  </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
