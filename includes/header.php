<?php
require_once __DIR__ . '/../auth.php';

$user = function_exists('current_user') ? current_user() : null;

// small helper to detect admin flag
function is_admin_user($user) {
    if (!$user) return false;
    if (isset($user['role']) && strtolower($user['role']) === 'admin') return true;
    if (isset($user['is_admin']) && (int)$user['is_admin'] === 1) return true;
    if (isset($user['admin']) && (int)$user['admin'] === 1) return true;
    if (isset($user['role_id']) && (int)$user['role_id'] === 1) return true;
    return false;
}

$user_name = $user['name'] ?? $_SESSION['user_name'] ?? 'Guest';
$showAdmin = is_admin_user($user);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Attendance System</title>

  <!-- Prevent flash: apply saved or system theme before paint -->
<script>
  (function () {
    try {
      var t = localStorage.getItem('theme');
      if (t === 'dark') document.documentElement.classList.add('dark');
      else if (t === 'light') document.documentElement.classList.remove('dark');
      else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.documentElement.classList.add('dark');
      }
    } catch (e) {}
  })();
</script>

<!-- Configure Tailwind Play CDN to use class-based dark mode -->
<script>
  window.tailwind = window.tailwind || {};
  tailwind.config = { darkMode: 'class' };
</script>

<!-- Tailwind CDN (must be AFTER the config) -->
<script src="https://cdn.tailwindcss.com"></script>

  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
<header class="bg-white shadow">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-16">
      <div class="flex items-center">
        <a href="index.php" class="text-xl font-bold text-gray-800 hover:text-blue-600">📌 Attendance</a>
        <nav class="hidden md:flex ml-6 space-x-4 text-sm">
          <a href="index.php" class="text-gray-600 hover:text-blue-600">Home</a>
          <a href="reports.php" class="text-gray-600 hover:text-blue-600">Reports</a>
          <a href="late_summary.php" class="text-gray-600 hover:text-blue-600">Summary</a>
          <?php if ($showAdmin): ?>
            <a href="admin.php" class="text-gray-600 hover:text-blue-600">Admin</a>
          <?php endif; ?>
        </nav>
      </div>

      <div class="flex items-center space-x-3">
        <span class="text-sm text-gray-600">👤 <?= htmlspecialchars($user_name) ?></span>
        <a href="logout.php" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm">Logout</a>
      </div>

      <!-- mobile menu toggle -->
      <div class="md:hidden">
        <button id="mobile-menu-button" aria-label="Toggle menu" class="p-2 rounded-md text-gray-600 hover:bg-gray-100">
          <!-- hamburger icon -->
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile menu (hidden by default) -->
  <div id="mobile-menu" class="md:hidden hidden border-t border-gray-100">
    <div class="px-2 pt-2 pb-3 space-y-1">
      <a href="index.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50">Home</a>
      <a href="reports.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50">Reports</a>
      <a href="late_summary.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50">Summary</a>
      <?php if ($showAdmin): ?>
        <a href="admin.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50">Admin</a>
      <?php endif; ?>
      <a href="logout.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50">Logout</a>
    </div>
  </div>
</header>

<main class="flex-1 max-w-7xl mx-auto w-full px-4 py-6">
<script>
  // mobile menu toggle
  (function(){
    var btn = document.getElementById('mobile-menu-button');
    if (!btn) return;
    btn.addEventListener('click', function(){
      var m = document.getElementById('mobile-menu');
      if (!m) return;
      m.classList.toggle('hidden');
    });
  })();
</script>
