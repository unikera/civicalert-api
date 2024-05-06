<?php

namespace App\Application\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtAuthenticationMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $response = new Response();

        $authHeader = $request->getHeaderLine('Authorization');
        $token = $authHeader ? explode(' ', $authHeader)[1] : null;

        if (!$token) {
            $response->getBody()->write(json_encode(['error' => 'Probleme de connexion.']));
            return $response->withStatus(401);
        }

        $key = 'votre_cle_secrete';

        try {
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            return $handler->handle($request);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Token invalid: ' . $e->getMessage()]));
            return $response->withStatus(401);
        }
    }
}
