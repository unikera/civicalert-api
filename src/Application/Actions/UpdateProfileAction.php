<?php
namespace App\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use PDO;

/**
 * @OA\Put(
 *     path="/modification",
 *     tags={"Utilisateur"},
 *     summary="Mise à jour du profil utilisateur",
 *     description="Permet à un utilisateur de mettre à jour son profil",
 *     security={{ "bearerAuth": {} }},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Données pour la mise à jour du profil",
 *         @OA\JsonContent(
 *             required={},
 *             @OA\Property(property="mail", type="string", example="user@example.com"),
 *             @OA\Property(property="tel", type="string", example="0123456789"),
 *             @OA\Property(property="pseudo", type="string", example="nouveauPseudo"),
 *             @OA\Property(property="mdp", type="string", example="nouveauMotDePasse")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Profil mis à jour avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="statuts", type="string", example="ok")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Mauvaise requête",
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="string", example="ERREUR"),
 *             @OA\Property(property="erreur", type="string", example="Aucune donnée à modifier.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur",
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="string", example="ERREUR"),
 *             @OA\Property(property="erreur", type="string", example="Message d'erreur")
 *         )
 *     )
 * )
 */


class UpdateProfileAction {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response): Response {
        $data = $request->getParsedBody();
        $updates = [];
        $params = [];

        if (isset($data['mail'])) {
            $updates[] = 'mail = ?';
            $params[] = $data['mail'];
        }
        if (isset($data['tel'])) {
            $updates[] = 'tel = ?';
            $params[] = $data['tel'];
        }
        if (isset($data['pseudo'])) {
            $updates[] = 'pseudo = ?';
            $params[] = $data['pseudo'];
        }
        if (isset($data['mdp'])) {
            $updates[] = 'mdp = ?';
            $params[] = password_hash($data['mdp'], PASSWORD_DEFAULT);
        }

        if (count($updates) > 0) {
            $sql = "UPDATE citoyens SET " . join(', ', $updates) . " WHERE id_citoyen = ?";
            $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
            $decodedToken = JWT::decode($token, new Key('votre_cle_secrete', 'HS256'));
            $userId = $decodedToken->sub;
            $params[] = $userId; 

            try {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);

                if ($stmt->rowCount() > 0) {
                    $response->getBody()->write(json_encode(['statuts' => 'ok']));
                } else {
                    $response->getBody()->write(json_encode(['code' => 'ERREUR', 'erreur' => 'Aucune modification effectuée.']));
                }
            } catch (\Exception $e) {
                $response->getBody()->write(json_encode(['code' => 'ERREUR', 'erreur' => $e->getMessage()]));
                return $response->withStatus(500);
            }
        } else {
            $response->getBody()->write(json_encode(['code' => 'ERREUR', 'erreur' => 'Aucune donnée à modifier.']));
            return $response->withStatus(400);
        }

        return $response->withHeader('Content-Type', 'application/json');
    }
}
