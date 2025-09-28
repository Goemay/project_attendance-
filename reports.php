<?php
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/db.php';

date_default_timezone_set('Asia/Jakarta');

$pdo = get_pdo();
$u   = current_user();
$isAdmin = ($u['role'] ?? 'user') === 'admin';
$userId  = (int)($u['id'] ?? 0);

// Read filters
$date_from = $_GET['date_from'] ?? '';
$date_to   = $_GET['date_to']   ?? '';
$type      = $_GET['type']      ?? '';
$uidParam  = $isAdmin ? ($_GET['user_id'] ?? '') : (string)$userId;

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 25;
$offset = ($page - 1) * $limit;

// WHERE
$where = [];
$params = [];
if (!$isAdmin) {
  $where[] = 'a.user_id = :uid';
  $params[':uid'] = $userId;
} else if ($uidParam !== '') {
  $where[] = 'a.user_id = :uid';
  $params[':uid'] = (int)$uidParam;
}
if ($date_from !== '') { $where[] = 'DATE(a.`timestamp`) >= :df'; $params[':df'] = $date_from; }
if ($date_to   !== '') { $where[] = 'DATE(a.`timestamp`) <= :dt'; $params[':dt'] = $date_to; }
if ($type === 'checkin' || $type === 'checkout') { $where[] = 'a.type = :type'; $params[':type'] = $type; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Totals
$stmt = $pdo->prepare("
  SELECT COUNT(*) AS total_rows,
         SUM(a.type='checkin')  AS total_checkins,
         SUM(a.type='checkout') AS total_checkouts,
         COUNT(DISTINCT a.user_id) AS distinct_users,
         SUM(a.note='Late') AS total_late
    FROM attendance a
    $whereSql
");
$stmt->execute($params);
$totals = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_rows'=>0,'total_checkins'=>0,'total_checkouts'=>0,'distinct_users'=>0,'total_late'=>0];

// Rows
$sql = "
  SELECT a.id, u.name AS user_name, a.user_id, a.type, a.timestamp, a.lat, a.lon, a.note
    FROM attendance a
    LEFT JOIN users u ON a.user_id = u.id
    $whereSql
   ORDER BY a.timestamp DESC
   LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Users for admin filter
$usersList = $isAdmin ? $pdo->query("SELECT id, name FROM users ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC) : [];

// Export URL
$query = ['date_from'=>$date_from, 'date_to'=>$date_to, 'type'=>$type];
if ($isAdmin && $uidParam !== '') $query['user_id'] = $uidParam;
$exportUrl = 'export_reports.php?' . http_build_query($query);

include __DIR__ . '/includes/header.php';
?>
<?php include __DIR__ . '/Universal_button.php'; ?>

<main class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-6">
  <div class="flex items-center justify-between gap-3">
    <h1 class="text-2xl font-semibold text-slate-900"><?= $isAdmin ? 'All reports' : 'My report' ?></h1>
    <div class="flex items-center gap-2">
      <a href="<?= htmlspecialchars($exportUrl) ?>"
         class="inline-flex items-center rounded-lg bg-emerald-600 px-3.5 py-2 text-white font-medium shadow-sm hover:bg-emerald-500">Export CSV</a>
      </div>
  </div>

  <form method="get" class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
      <div>
        <label class="block text-sm font-medium text-slate-700">From</label>
        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">To</label>
        <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700">Type</label>
        <select name="type" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
          <option value="" <?= $type===''?'selected':'' ?>>All</option>
          <option value="checkin" <?= $type==='checkin'?'selected':'' ?>>Check in</option>
          <option value="checkout" <?= $type==='checkout'?'selected':'' ?>>Check out</option>
        </select>
      </div>
      <?php if ($isAdmin): ?>
      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-slate-700">User</label>
        <select name="user_id" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
          <option value="">All users</option>
          <?php foreach ($usersList as $uu): ?>
            <option value="<?= (int)$uu['id'] ?>" <?= ($uidParam!=='' && (int)$uidParam===(int)$uu['id'])?'selected':'' ?>><?= htmlspecialchars($uu['name'] ?? ('User '.$uu['id'])) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
    </div>
    <div class="mt-3">
      <button class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-white font-semibold shadow-sm hover:bg-indigo-500">Apply filters</button>
          <a href="reports.php"
         class="inline-flex items-center rounded-lg bg-white border border-slate-200 px-3.5 py-2 text-slate-700 hover:bg-slate-50 shadow-sm">Reset</a>
    </div>
  </form>

  <div class="mt-4 grid grid-cols-2 sm:grid-cols-5 gap-3">
    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm"><div class="text-xs text-slate-500">Rows</div><div class="text-lg font-semibold"><?= (int)$totals['total_rows'] ?></div></div>
    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm"><div class="text-xs text-slate-500">Check‑ins</div><div class="text-lg font-semibold"><?= (int)$totals['total_checkins'] ?></div></div>
    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm"><div class="text-xs text-slate-500">Check‑outs</div><div class="text-lg font-semibold"><?= (int)$totals['total_checkouts'] ?></div></div>
    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm"><div class="text-xs text-slate-500">Users</div><div class="text-lg font-semibold"><?= (int)$totals['distinct_users'] ?></div></div>
    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm"><div class="text-xs text-slate-500">Late</div><div class="text-lg font-semibold"><?= (int)$totals['total_late'] ?></div></div>
  </div>

  <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-slate-200">
      <thead class="bg-slate-50">
        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-600">
          <th class="px-4 py-3">ID</th>
          <th class="px-4 py-3">User</th>
          <th class="px-4 py-3">Type</th>
          <th class="px-4 py-3">Timestamp</th>
          <th class="px-4 py-3">Location</th>
          <th class="px-4 py-3">Status</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 text-sm">
        <?php foreach ($rows as $row): ?>
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3"><?= (int)$row['id'] ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($row['user_name'] ?? 'Unknown') ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($row['type']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($row['timestamp']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars(($row['lat'] ?? '') . ', ' . ($row['lon'] ?? '')) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($row['note'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php
    $totalRows = (int)$totals['total_rows'];
    $totalPages = (int)ceil($totalRows / $limit);
    if ($totalPages < 1) $totalPages = 1;
  ?>
  <div class="mt-4 flex items-center justify-between">
    <div class="text-sm text-slate-600">Page <?= $page ?> of <?= $totalPages ?></div>
    <div class="flex items-center gap-2">
      <?php if ($page > 1): ?>
        <a class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50" href="?<?= http_build_query(array_merge($_GET, ['page'=>$page-1])) ?>">Prev</a>
      <?php endif; ?>
      <?php if ($page < $totalPages): ?>
        <a class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50" href="?<?= http_build_query(array_merge($_GET, ['page'=>$page+1])) ?>">Next</a>
      <?php endif; ?>
    </div>
  </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
