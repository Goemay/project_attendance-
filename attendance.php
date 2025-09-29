<?php
// attendance.php — uses open_from and on_time_deadline from settings
require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/db.php';

date_default_timezone_set('Asia/Jakarta');

$u = function_exists('current_user') ? current_user() : null;
$userId   = (int)($u['id'] ?? ($_SESSION['user_id'] ?? 0));
$userName = htmlspecialchars($u['name'] ?? ($_SESSION['user_name'] ?? 'User'), ENT_QUOTES, 'UTF-8');
if ($userId <= 0) {
    header('Location: login.php');
    exit;
}

$pdo = get_pdo();

// Load settings, including new editable thresholds
$settings = $pdo->query("
  SELECT allowed_lat, allowed_lon, radius,
         work_start, work_end,
         open_from, on_time_deadline
    FROM settings
   LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

// Thresholds now come from settings with safe fallbacks
$openFrom       = $settings['open_from']        ?? '07:00:00'; // before this → not started
$onTimeDeadline = $settings['on_time_deadline'] ?? ($settings['work_start'] ?? '09:00:00'); // <= on time; > late

// ---- Haversine distance in meters ----
function haversine_m($lat1, $lon1, $lat2, $lon2) {
    $R = 6371000; // meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2)
       + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
       * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

// ---- Determine today's state (can_check_in vs can_check_out) ----
$todayLast = $pdo->prepare("
    SELECT *
      FROM attendance
     WHERE user_id = ?
       AND DATE(`timestamp`) = CURDATE()
  ORDER BY `timestamp` DESC
     LIMIT 1
");
$todayLast->execute([$userId]);
$lastRow = $todayLast->fetch(PDO::FETCH_ASSOC);

$state           = 'can_check_in';
$activeCheckInAt = null;
$activeNote      = null;

if ($lastRow && $lastRow['type'] === 'checkin') {
    $state           = 'can_check_out';
    $activeCheckInAt = $lastRow['timestamp'];
    $activeNote      = $lastRow['note'] ?? null;
}

// ---- Handle POST actions ----
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $lat      = isset($_POST['lat']) && $_POST['lat'] !== '' ? (float)$_POST['lat'] : null;
    $lon      = isset($_POST['lon']) && $_POST['lon'] !== '' ? (float)$_POST['lon'] : null;
    $accuracy = isset($_POST['accuracy']) && $_POST['accuracy'] !== '' ? (float)$_POST['accuracy'] : null;

    $now  = new DateTime('now');
    $tNow = $now->format('H:i:s');

    // Geofence check
    $outsideRadius = false;
    if ($settings && $lat !== null && $lon !== null && $settings['allowed_lat'] !== null && $settings['allowed_lon'] !== null) {
        $allowedLat = (float)$settings['allowed_lat'];
        $allowedLon = (float)$settings['allowed_lon'];
        $radius     = (float)($settings['radius'] ?? 0); // meters
        if ($radius > 0) {
            $dist = haversine_m($lat, $lon, $allowedLat, $allowedLon);
            $outsideRadius = $dist > $radius;
        }
    }

    try {
        if ($action === 'checkin') {
            if ($state !== 'can_check_in') {
                $msg = 'Already checked in; please check out first.';
            } elseif ($tNow < $openFrom) {
                $msg = 'Attendance has not started yet.';
            } elseif ($settings && ($lat === null || $lon === null)) {
                $msg = 'Location is required for check in.';
            } elseif ($outsideRadius) {
                $msg = 'Outside allowed area; check in blocked.';
            } else {
                $isLate = ($tNow > $onTimeDeadline);
                $note   = $isLate ? 'Late' : 'On time';

                $ins = $pdo->prepare("
                    INSERT INTO attendance (user_id, type, `timestamp`, lat, lon, accuracy, note)
                    VALUES (?, 'checkin', NOW(), ?, ?, ?, ?)
                ");
                $ins->execute([$userId, $lat, $lon, $accuracy, $note]);

                header('Location: attendance.php?checked_in=1');
                exit;
            }
        } elseif ($action === 'checkout') {
            if ($state !== 'can_check_out') {
                $msg = 'No active check in found for today.';
            } else {
                $note = 'Checked out';

                $ins = $pdo->prepare("
                    INSERT INTO attendance (user_id, type, `timestamp`, lat, lon, accuracy, note)
                    VALUES (?, 'checkout', NOW(), ?, ?, ?, ?)
                ");
                $ins->execute([$userId, $lat, $lon, $accuracy, $note]);

                header('Location: attendance.php?checked_out=1');
                exit;
            }
        }
    } catch (Throwable $e) {
        $msg = 'Database error: ' . $e->getMessage();
    }
}

include __DIR__ . '/includes/header.php';

// Back/Dashboard/Admin bar (compact)
$isAdmin = (($u['role'] ?? '') === 'admin');
?>
<?php include __DIR__ . '/includes/Universal_button.php'; ?>

  <!-- User card with live clock and state -->
  <div style="max-width:100%;margin:12px 0 10px 0;padding:14px;border:1px solid #e5e7eb;border-radius:12px;background:#fff;" class="shadow-sm">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px">
      <div>
        <h2 style="margin:0;font-size:18px;color:#0f172a">Welcome, <?= $userName ?></h2>
        <div id="clock" style="font-size:14px;color:#64748b"></div>
      </div>
      <div>
        <?php if ($state === 'can_check_in'): ?>
          <span style="padding:6px 10px;border-radius:8px;background:#eef2ff;color:#3730a3">Ready to check in</span>
        <?php else: ?>
          <span style="padding:6px 10px;border-radius:8px;background:#ecfdf5;color:#065f46">
            Checked in at <?= htmlspecialchars($activeCheckInAt) ?>
          </span>
          <?php if ($activeNote): ?>
            <span style="padding:6px 10px;border-radius:8px;background:#fffbeb;color:#92400e;margin-left:6px">
              <?= htmlspecialchars($activeNote) ?>
            </span>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if (!empty($_GET['checked_in'])): ?>
    <div class="mt-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-900 text-sm">Check in recorded.</div>
  <?php elseif (!empty($_GET['checked_out'])): ?>
    <div class="mt-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-900 text-sm">Check out recorded.</div>
  <?php elseif ($msg): ?>
    <div class="mt-2 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-rose-900 text-sm"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <form id="attForm" method="post" class="mt-3">
    <input type="hidden" name="action" id="actionField" value="">
    <input type="hidden" name="lat" id="latField">
    <input type="hidden" name="lon" id="lonField">
    <input type="hidden" name="accuracy" id="accField">

    <div class="flex gap-2 flex-wrap">
      <?php if ($state === 'can_check_in'): ?>
        <button type="button" id="btnCheckIn"
                class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-white font-semibold shadow-sm hover:bg-indigo-500">
          Check in
        </button>
      <?php else: ?>
        <button type="button" id="btnCheckOut"
                class="inline-flex items-center rounded-lg bg-rose-600 px-4 py-2.5 text-white font-semibold shadow-sm hover:bg-rose-500">
          Check out
        </button>
      <?php endif; ?>
    </div>

    <p class="mt-3 text-sm text-slate-600">
      Rules: before <?= htmlspecialchars($openFrom) ?> attendance is closed; between <?= htmlspecialchars($openFrom) ?> and <?= htmlspecialchars($onTimeDeadline) ?> is on time; after <?= htmlspecialchars($onTimeDeadline) ?> is late (still allowed).
    </p>
  </form>
</main>

<script>
const clockEl = document.getElementById('clock');
function tick(){ const d=new Date(); clockEl.textContent = d.toLocaleString(); }
tick(); setInterval(tick,1000);

function getPosition(){
  return new Promise((resolve,reject)=>{
    if(!navigator.geolocation) return reject(new Error('Geolocation not supported'));
    navigator.geolocation.getCurrentPosition(
      pos=>resolve(pos.coords),
      err=>reject(err),
      { enableHighAccuracy:true, timeout:10000, maximumAge:0 }
    );
  });
}
async function doSubmit(action){
  document.getElementById('actionField').value = action;
  try{
    const c = await getPosition();
    document.getElementById('latField').value = c.latitude;
    document.getElementById('lonField').value = c.longitude;
    document.getElementById('accField').value = c.accuracy ?? '';
  }catch(e){
    alert('Could not read location; submitting without coordinates.');
  }
  document.getElementById('attForm').submit();
}
document.getElementById('btnCheckIn')?.addEventListener('click', ()=>doSubmit('checkin'));
document.getElementById('btnCheckOut')?.addEventListener('click',()=>doSubmit('checkout'));
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
