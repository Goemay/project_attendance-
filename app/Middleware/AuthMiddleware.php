<?php
namespace App\Middleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class AuthMiddleware {
    public function __invoke(Request $request, Response $response, $next) {
        if (!isset($_SESSION['user_id'])) {
            $response->getBody()->write('Unauthorized: Please log in.');
            return $response->withStatus(401);
        }
        return $next($request, $response);
    }
}
