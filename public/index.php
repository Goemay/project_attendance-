<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../db.php';

use Slim\Factory\AppFactory;
use App\Middleware\AuthMiddleware;

session_start();

$app = AppFactory::create();

// Home route
$app->get('/', function ($request, $response, $args) {
    $response->getBody()->write('<h1>Welcome to Project Attendance!</h1>');
    return $response;
});

// NEW (in public/index.php)
$app->get('/dashboard', function ($request, $response, $args) {
    // Paste your dashboard logic here
    $response->getBody()->write('<h2>Dashboard</h2>');
    return $response;
});

// Attendance API route (protected, returns JSON)
$app->get('/api/attendance', function ($request, $response, $args) {
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        $response->getBody()->write(json_encode(['error' => 'Not logged in']));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM attendance WHERE user_id = ? ORDER BY timestamp DESC');
    $stmt->execute([$userId]);
    $records = $stmt->fetchAll();
    $response->getBody()->write(json_encode($records));
    return $response->withHeader('Content-Type', 'application/json');
});

// Add authentication middleware globally
$app->add(new AuthMiddleware());

$app->run();
