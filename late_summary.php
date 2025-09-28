<?php
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/db.php';
date_default_timezone_set('Asia/Jakarta');

$pdo = get_pdo();
$u   = current_user();
$isAdmin = ($u['role'] ?? 'user') === 'admin';
$userId  = (int)($u['id'] ?? 0);

// Latest record per user (admin) or only for the current user (non-admin)
if ($isAdmin) {
    $stmt = $pdo->query("
      SELECT u.id AS user_id, u.name AS user_name, a.type, a.timestamp, a.note
      FROM users u
      JOIN (
        SELECT x1.*
        FROM attendance x1
        JOIN (
          SELECT user_id, MAX(`timestamp`) AS max_ts
          FROM attendance
          GROUP BY user_id
        ) x2 ON x1.user_id = x2.user_id AND x1.`timestamp` = x2.max_ts
      ) a ON a.user_id = u.id
      ORDER BY u.name ASC
    ");
} else {
    $stmt = $pdo->prepare("
      SELECT u.id AS user_id, u.name AS user_name, a.type, a.timestamp, a.note
      FROM users u
      JOIN (
        SELECT *
        FROM attendance
        WHERE user_id = ?
        ORDER BY `timestamp` DESC
        LIMIT 1
      ) a ON a.user_id = u.id
      WHERE u.id = ?
    ");
    $stmt->execute([$userId, $userId]);
}
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php';
?>
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

<main class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-6">
  <h1 class="text-2xl font-semibold text-slate-900"><?= $isAdmin ? 'Late summary (all users)' : 'My late summary' ?></h1>
  <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-slate-200">
      <thead class="bg-slate-50">
        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-600">
          <th class="px-4 py-3">User</th>
          <th class="px-4 py-3">Last type</th>
          <th class="px-4 py-3">Last time</th>
          <th class="px-4 py-3">Status</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 text-sm">
        <?php foreach ($rows as $row): ?>
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3"><?= htmlspecialchars($row['user_name'] ?? '') ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($row['type'] ?? '') ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($row['timestamp'] ?? '') ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($row['note'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
