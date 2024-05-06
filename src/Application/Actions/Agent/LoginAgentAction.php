<?php
namespace App\Application\Actions\Agent;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;

use App\Application\Actions\Agent\AgentService;

/**
 * @OA\Post(
 *     path="/agent/login",
 *     tags={"Agent"},
 *     summary="Connexion de l'agent",
 *     description="Permet à un agent de se connecter en utilisant son email et son mot de passe",
 *     @OA\RequestBody(
 *         required=true,
 *         description="Email et mot de passe de l'agent",
 *         @OA\JsonContent(
 *             required={"mail", "mdp"},
 *             @OA\Property(property="mail", type="string", example="agent@example.com"),
 *             @OA\Property(property="mdp", type="string", example="motdepasseAgent")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Connexion réussie",
 *         @OA\JsonContent(
 *             @OA\Property(property="idAgent", type="integer", example=1),
 *             @OA\Property(property="nom", type="string", example="Doe"),
 *             @OA\Property(property="prenom", type="string", example="John"),
 *             @OA\Property(property="tel", type="string", example="0123456789"),
 *             @OA\Property(property="pseudo", type="string", example="agentDoe"),
 *             @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Identifiants incorrects",
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="string", example="auth_failed"),
 *             @OA\Property(property="erreur", type="string", example="Les identifiants fournis sont incorrects.")
 *         )
 *     )
 * )
 */

 
class LoginAgentAction
{
    private $agentService;

    public function __construct(AgentService $agentService) {
        $this->agentService = $agentService;
    }

    public function __invoke(Request $request, Response $response): Response {
        $data = $request->getParsedBody();
        $email = $data['mail'] ?? '';
        $password = $data['mdp'] ?? '';

        $user = $this->agentService->authenticate($email, $password);

        if ($user) {
            $key = "votre_cle_secrete"; // Utilisez une clé secrète forte
            $payload = [
                'iat' => time(), // Issued at: time when the token was generated
                'exp' => time() + 3600, // Expiration time
                'sub' => $user['id_agent'], // Subject of the token (the user id)
            ];

            $jwt = JWT::encode($payload, $key, 'HS256');

            $response->getBody()->write(json_encode([
                'idAgent' => $user['id_agent'],
                'nom' => $user['nom'],
                'prenom' => $user['prenom'],
                'tel' => $user['tel'],
                'pseudo' => $user['pseudo'],
                'token' => $jwt // JWT token
            ]));
        } else {
            $response->getBody()->write(json_encode([
                'code' => "auth_failed",
                'erreur' => "Les identifiants fournis sont incorrects.",
            ]));
            return $response->withStatus(401); // Unauthorized
        }

        return $response->withHeader('Content-Type', 'application/json');
    }
}
