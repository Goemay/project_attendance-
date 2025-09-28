<?php
// Slim front controller - optional. If you prefer the simple PHP pages, you can ignore this.
// This file is safe to keep even if Composer/vendor dependencies are not installed.
$vendor = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendor)) {
    // Friendly fallback page when dependencies are missing
    http_response_code(503);
    echo "<!doctype html><html><head><meta charset=\"utf-8\"><title>Dependencies missing</title></head><body>";
    echo "<h1>Composer dependencies not installed</h1>";
    echo "<p>The optional Slim-based scaffold requires Composer dependencies which are not present.</p>";
    echo "<p>To enable the Slim app, install Composer and run <code>composer install</code> in the project root (use your XAMPP PHP binary for CLI).</p>";
    echo "<p>For now you can use the legacy pages: <a href=\"../index.php\">Open legacy app</a></p>";
    echo "</body></html>";
    exit;
}

require $vendor;

use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Factory\AppFactory;

// Load .env if available
if (file_exists(__DIR__ . '/../.env')) {
    if (class_exists('\Dotenv\Dotenv')) {
        (\Dotenv\Dotenv::createImmutable(__DIR__ . '/../'))->load();
    }
}

$app = AppFactory::create();
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write("Slim entry - optional. Keep using legacy PHP files or migrate routes.");
    return $response;
});

$app->run();
