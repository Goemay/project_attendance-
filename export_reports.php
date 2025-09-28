<?php
// export_reports.php — CSV export of filtered attendance

require_once __DIR__ . '/auth.php';
require_login();
require_once __DIR__ . '/db.php';

date_default_timezone_set('Asia/Jakarta');

$pdo = get_pdo();
$u   = current_user();
$isAdmin = ($u['role'] ?? 'user') === 'admin';
$userId  = (int)($u['id'] ?? 0);

// Filters (same as reports.php)
$date_from = $_GET['date_from'] ?? '';
$date_to   = $_GET['date_to']   ?? '';
$type      = $_GET['type']      ?? '';
$uidParam  = $isAdmin ? ($_GET['user_id'] ?? '') : (string)$userId;

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

$sql = "
  SELECT a.id, u.name AS user_name, a.user_id, a.type, a.timestamp, a.lat, a.lon, a.accuracy, a.note
    FROM attendance a
    LEFT JOIN users u ON a.user_id = u.id
    $whereSql
   ORDER BY a.timestamp DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Stream CSV
$filename = 'attendance_export_' . date('Ymd_His') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');
$fp = fopen('php://output', 'w');
fputcsv($fp, ['ID','User','User ID','Type','Timestamp','Lat','Lon','Accuracy','Status']);
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
  fputcsv($fp, [
    $r['id'],
    $r['user_name'] ?? 'Unknown',
    $r['user_id'],
    $r['type'],
    $r['timestamp'],
    $r['lat'],
    $r['lon'],
    $r['accuracy'],
    $r['note'] ?? ''
  ]);
}
fclose($fp);
exit;
