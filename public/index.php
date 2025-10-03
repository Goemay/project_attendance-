<?php
// public/index.php — Slim 4 front controller

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../db.php';

use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Sessions for auth
session_start();

// Create app
$app = AppFactory::create();

// Base path if served as http://localhost/Project-cnaindo/public (optional)
// Uncomment if routing fails due to subdirectory
// $app->setBasePath('/Project-cnaindo/public'); // adjust if needed [see README]

// Error middleware (show details in dev)
$errorMiddleware = $app->addErrorMiddleware(true, true, true); // display, log, log details [web:363]

// Optional: routing middleware (Slim 4 usually adds automatically via AppFactory)
$app->addRoutingMiddleware();

// Absolute path to Windows venv Python
$python = escapeshellarg(__DIR__ . '/../.venv/Scripts/python.exe');

// -------------------- Routes --------------------

// Home
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write('<h1>Welcome to Project Attendance!</h1>');
    return $response;
});

// Dashboard (placeholder)
$app->get('/dashboard', function (Request $request, Response $response) {
    $response->getBody()->write('<h2>Dashboard</h2>');
    return $response;
});

// JSON API: recent attendance for logged-in user
$app->get('/api/attendance', function (Request $request, Response $response) {
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        $payload = json_encode(['error' => 'Not logged in']);
        $response->getBody()->write($payload);
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM attendance WHERE user_id = ? ORDER BY `timestamp` DESC');
    $stmt->execute([$userId]);
    $records = $stmt->fetchAll();
    $response->getBody()->write(json_encode($records));
    return $response->withHeader('Content-Type', 'application/json');
});

// Face verification endpoint — called by attendance.php before check-in
$app->post('/api/face-verify', function (Request $request, Response $response) use ($python) {
    $data = json_decode((string)$request->getBody(), true);
    if (!is_array($data)) { $data = (array)$request->getParsedBody(); }

    $userId = (int)($data['user_id'] ?? 0);
    $imgB64 = $data['image_base64'] ?? '';

    if ($userId <= 0 || !$imgB64) {
        $response->getBody()->write(json_encode(['ok'=>false,'error'=>'bad_input']));
        return $response->withStatus(400)->withHeader('Content-Type','application/json');
    }

    // Save temp PNG
    $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('face_', true) . '.png';
    file_put_contents($tmp, base64_decode($imgB64));

    // Pass DB env to Python (read by verify.py via os.getenv)
    $envExport = '';
    foreach (['DB_HOST','DB_USERNAME','DB_PASSWORD','DB_DATABASE'] as $k) {
        $v = getenv($k);
        if ($v !== false) { $envExport .= $k.'=' . escapeshellarg($v) . ' '; }
    }

    $script = escapeshellarg(__DIR__ . '/../face/verify.py');
    // Redirect stderr to stdout with 2>&1 so errors appear in $out [web:40][web:368]
    $cmd = $envExport . $python . ' ' . $script . ' ' .
           escapeshellarg('--user-id='.$userId) . ' ' .
           escapeshellarg('--image='.$tmp) . ' 2>&1';

    // Optional log for troubleshooting
    $logDir = __DIR__ . '/../storage/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0775, true);
    $logFile = $logDir . '/face.log';
    file_put_contents($logFile, "[".date('c')."] CMD: $cmd\n", FILE_APPEND);

    $out = shell_exec($cmd);
    @unlink($tmp);

    file_put_contents($logFile, "[".date('c')."] OUT: $out\n", FILE_APPEND);

    $res = json_decode($out ?? '', true);
    if (!$res) {
        $response->getBody()->write(json_encode(['ok'=>false,'error'=>'python_error']));
        return $response->withStatus(500)->withHeader('Content-Type','application/json');
    }

    $response->getBody()->write(json_encode([
        'ok'=>true,
        'match'=>$res['match'] ?? false,
        'distance'=>$res['distance'] ?? null
    ]));
    return $response->withHeader('Content-Type','application/json');
});

// ------------------------------------------------

$app->run();
