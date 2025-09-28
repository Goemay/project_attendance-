<?php
$me = function_exists('current_user') ? current_user() : null;
$isAdmin = (($me['role'] ?? '') === 'admin');
?>
<div class="mb-4 flex items-center gap-2 justify-end">

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