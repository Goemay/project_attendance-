<?php
// settings_admin.php — edit geofence and time thresholds (admin only)

require_once __DIR__ . '/auth.php';
require_admin();
require_once __DIR__ . '/db.php';

date_default_timezone_set('Asia/Jakarta');
$pdo = get_pdo();

$ok = ''; $err = '';

// Fetch current row (assume single row id=1)
$settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$settings) {
    // Create default row if missing
    $pdo->exec("INSERT INTO settings (id, allowed_lat, allowed_lon, radius, work_start, work_end, open_from, on_time_deadline, updated_at)
                VALUES (1, NULL, NULL, 0, '09:00:00', '17:00:00', '07:00:00', '09:00:00', NOW())");
    $settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $err = 'Invalid request token.';
    } else {
        try {
            $lat     = $_POST['allowed_lat'] !== '' ? (float)$_POST['allowed_lat'] : null;
            $lon     = $_POST['allowed_lon'] !== '' ? (float)$_POST['allowed_lon'] : null;
            $radius  = $_POST['radius']       !== '' ? (float)$_POST['radius']      : 0;

            $work_start = $_POST['work_start'] ?? null;
            $work_end   = $_POST['work_end']   ?? null;

            $open_from        = $_POST['open_from']        ?? null;
            $on_time_deadline = $_POST['on_time_deadline'] ?? null;

            // Normalize to HH:MM:SS if value like HH:MM
            foreach (['work_start','work_end','open_from','on_time_deadline'] as $k) {
                if ($$k !== null && $$k !== '') {
                    if (strlen($$k) === 5) $$k .= ':00';
                } else {
                    $$k = null;
                }
            }

            $stmt = $pdo->prepare("
                UPDATE settings
                   SET allowed_lat = ?, allowed_lon = ?, radius = ?,
                       work_start = ?, work_end = ?,
                       open_from = ?, on_time_deadline = ?,
                       updated_at = NOW()
                 WHERE id = 1
            ");
            $stmt->execute([$lat, $lon, $radius, $work_start, $work_end, $open_from, $on_time_deadline]);

            $ok = 'Settings updated successfully!';
            $settings = $pdo->query("SELECT * FROM settings LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $err = 'Update failed: ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/includes/header.php';
$isAdmin = true; // already enforced
?>
<main class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-4">
  <!-- Back/Dashboard/Admin -->
  <div class="mb-3 flex items-center gap-2">
    <button type="button"
            onclick="if (document.referrer) { history.back(); } else { window.location.href='admin.php'; }"
            class="inline-flex items-center rounded-lg bg-white border border-slate-200 px-3 py-2 text-slate-700 hover:bg-slate-50 shadow-sm">
      ← Back
    </button>
    <a href="index.php"
       class="inline-flex items-center rounded-lg bg-slate-900 px-3.5 py-2 text-white font-medium shadow-sm hover:bg-slate-800">
      Dashboard
    </a>
    <a href="admin.php"
       class="inline-flex items-center rounded-lg bg-indigo-600 px-3.5 py-2 text-white font-medium shadow-sm hover:bg-indigo-500">
      Admin
    </a>
  </div>

  <h1 class="text-2xl font-semibold text-slate-900">Attendance settings</h1>
  <p class="text-slate-500 text-sm mt-1">Configure geofence and time thresholds.</p>

  <?php if ($ok): ?>
    <div class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-900 text-sm"><?= htmlspecialchars($ok, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>
  <?php if ($err): ?>
    <div class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-rose-900 text-sm"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <form method="post" class="mt-4 space-y-5">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

    <!-- Geofence -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-base font-semibold text-slate-900">Geofence</h2>
      <p class="text-slate-500 text-sm mt-1">Allowed location and radius in meters.</p>
      <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700">Allowed latitude</label>
          <input type="text" name="allowed_lat"
                 value="<?= htmlspecialchars((string)($settings['allowed_lat'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                 class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Allowed longitude</label>
          <input type="text" name="allowed_lon"
                 value="<?= htmlspecialchars((string)($settings['allowed_lon'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                 class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Radius (meters)</label>
          <input type="number" step="1" min="0" name="radius"
                 value="<?= htmlspecialchars((string)($settings['radius'] ?? 0), ENT_QUOTES, 'UTF-8') ?>"
                 class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>
      </div>
    </div>

    <!-- Time thresholds -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <h2 class="text-base font-semibold text-slate-900">Time windows</h2>
      <p class="text-slate-500 text-sm mt-1">Control when attendance opens and when check-ins become late.</p>

      <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700">Open from (earliest check‑in)</label>
          <input type="time" name="open_from"
                 value="<?= htmlspecialchars(substr((string)($settings['open_from'] ?? '07:00:00'),0,5), ENT_QUOTES, 'UTF-8') ?>"
                 class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
          <p class="text-xs text-slate-500 mt-1">Before this, attendance shows “not started yet”.</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">On‑time deadline</label>
          <input type="time" name="on_time_deadline"
                 value="<?= htmlspecialchars(substr((string)($settings['on_time_deadline'] ?? ($settings['work_start'] ?? '09:00:00')),0,5), ENT_QUOTES, 'UTF-8') ?>"
                 class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
          <p class="text-xs text-slate-500 mt-1">After this, check‑ins are marked “Late”.</p>
        </div>
      </div>

      <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700">Work start (legacy)</label>
          <input type="time" name="work_start"
                 value="<?= htmlspecialchars(substr((string)($settings['work_start'] ?? '09:00:00'),0,5), ENT_QUOTES, 'UTF-8') ?>"
                 class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
          <p class="text-xs text-slate-500 mt-1">Kept for compatibility; on‑time deadline above takes precedence.</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Work end (legacy)</label>
          <input type="time" name="work_end"
                 value="<?= htmlspecialchars(substr((string)($settings['work_end'] ?? '17:00:00'),0,5), ENT_QUOTES, 'UTF-8') ?>"
                 class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
          <p class="text-xs text-slate-500 mt-1">Not used by check‑in logic currently; retained for future use.</p>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <button class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-white font-semibold shadow-sm hover:bg-indigo-500" type="submit">
        Save settings
      </button>
      <a href="admin.php" class="text-sm text-slate-600 hover:text-slate-900">Back to admin</a>
    </div>
  </form>

  <div class="h-4"></div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>