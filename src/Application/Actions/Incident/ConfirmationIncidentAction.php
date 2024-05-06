<?php

namespace App\Application\Actions\Incident;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PDO;

/**
 * @OA\Post(
 *     path="/confirmer/{id}",
 *     tags={"Incidents"},
 *     summary="Confirme un incident",
 *     description="Permet à un utilisateur authentifié de confirmer un incident spécifique",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de l'incident à confirmer",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Incident confirmé avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="statuts", type="string", example="ok")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Non autorisé",
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="string", example="UNAUTHORIZED"),
 *             @OA\Property(property="erreur", type="string", example="Vous devez être connecté pour confirmer un incident.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Incident non trouvé",
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="string", example="NOT_FOUND"),
 *             @OA\Property(property="erreur", type="string", example="Incident non trouvé.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur",
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="string", example="ERREUR"),
 *             @OA\Property(property="erreur", type="string", example="Une erreur est survenue lors de la confirmation de l'incident.")
 *         )
 *     )
 * )
 */


class ConfirmationIncidentAction{
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args): Response {

        $incidentId = intval($args['id']);

        $userId = $this->getUserIdFromToken($request);
    
        if (!$userId) {
            $response->getBody()->write(json_encode([
                'code' => 'UNAUTHORIZED',
                'erreur' => 'Vous devez être connecté pour confirmer un incident.'
            ]));
            return $response->withStatus(401);
        }
    
        $incidentId = $incidentId ?? 0;

        if (!$this->incidentExists($incidentId)) {
            $response->getBody()->write(json_encode([
                'code' => 'NOT_FOUND',
                'erreur' => 'Incident non trouvé.'
            ]));
            return $response->withStatus(404);
        }
    
        $sql = "INSERT INTO confirmations (id_citoyen, id_incident) VALUES (?, ?)";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $incidentId]);
            $response->getBody()->write(json_encode(['statuts' => 'ok']));
            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'code' => 'ERREUR',
                'erreur' => 'Une erreur est survenue lors de la confirmation de l\'incident.'
            ]));
            return $response->withStatus(500);
        }
    }    

    private function getUserIdFromToken(Request $request) {
        $token = str_replace('Bearer ', '', $request->getHeaderLine('Authorization'));
        if (!$token) {
            return null;
        }
        try {
            $decodedToken = JWT::decode($token, new Key('votre_cle_secrete', 'HS256'));
            return $decodedToken->sub;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function incidentExists($incidentId) {

        $sql = "SELECT id_incident FROM incidents WHERE id_incident = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$incidentId]);

        return $stmt->rowCount() > 0;
    }
}