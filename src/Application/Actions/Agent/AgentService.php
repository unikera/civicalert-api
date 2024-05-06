<?php

namespace App\Application\Actions\Agent;
use PDOException;
use PDO;

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

 
class AgentService {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function authenticate($email, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT id_agent, nom, prenom, tel, pseudo, mdp FROM agents WHERE mail = :email LIMIT 1");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['mdp'])) {
                unset($user['mdp']); 
                return $user;
            } else {
                return null;
            }
            
        } catch (PDOException $e) {
            throw $e;
        }
    }
}
