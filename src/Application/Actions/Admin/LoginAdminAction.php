<?php

namespace App\Application\Actions\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;

use App\Application\Actions\Admin\AdminService;

class LoginAdminAction
{
    private $adminService;

    public function __construct(AdminService $adminService) {
        $this->adminService = $adminService;
    }

    public function __invoke(Request $request, Response $response): Response {
        $data = $request->getParsedBody();
        $email = $data['mail'] ?? '';
        $password = $data['mdp'] ?? '';

        $user = $this->adminService->authenticate($email, $password);

        if ($user) {
            $key = "votre_cle_secrete"; // Utilisez une clé secrète forte
            $payload = [
                'iat' => time(), // Issued at: time when the token was generated
                'exp' => time() + 3600, // Expiration time
                'sub' => $user['id_admin'], // Subject of the token (the user id)
            ];

            $jwt = JWT::encode($payload, $key, 'HS256');

            $response->getBody()->write(json_encode([
                'idAdmin' => $user['id_admin'],
                'nom' => $user['nom'],
                'prenom' => $user['prenom'],
                'mail' => $user['mail'],
                'token' => $jwt // JWT token
            ]));
        } else {
            $response->getBody()->write(json_encode([
                'code' => "auth_failed",
                'erreur' => "Les identifiants admin fournis sont incorrects.",
            ]));
            return $response->withStatus(401); // Unauthorized
        }

        return $response->withHeader('Content-Type', 'application/json');
    }
}
