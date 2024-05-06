<?php

namespace App\Application\Actions\Incident;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PDO;

/**
 * @OA\Delete(
 *     path="/supprimerIncident/{id}",
 *     tags={"Incidents"},
 *     summary="Supprime un incident",
 *     description="Supprime un incident spécifique si l'utilisateur est autorisé à le faire",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de l'incident à supprimer",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Incident supprimé avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="SUCCESS", type="string", example="Incident supprimé avec succès.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Non autorisé",
 *         @OA\JsonContent(
 *             @OA\Property(property="ERROR", type="string", example="Vous devez être connecté")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Interdit",
 *         @OA\JsonContent(
 *             @OA\Property(property="ERROR", type="string", example="Vous ne pouvez pas supprimer cet incident")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur",
 *         @OA\JsonContent(
 *             @OA\Property(property="ERROR", type="string", example="Échec")
 *         )
 *     )
 * )
 */


 class SupIncidentAction
{
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args): Response {
        $incidentId = intval($args['id']);

        $userId = $this->getUserIdFromToken($request);

        if (!$userId) {
            $response->getBody()->write(json_encode(['ERROR' => 'Vous devez être connecté']));
            return $response->withStatus(401);
        }

        if (!$this->canDeleteIncident($incidentId, $userId)) {
            $response->getBody()->write(json_encode(['ERROR' => 'Vous ne pouvez pas supprimez cet incident']));
            return $response->withStatus(403);
        }

        if ($this->deleteIncident($incidentId)) {
            $response->getBody()->write(json_encode(['SUCESS ' => 'Incident supprimé avec succès.']));
        } else {
            $response->getBody()->write(json_encode(['ERROR ' => 'Echec']));
            return $response->withStatus(500);
        }

        return $response->withHeader('Content-Type', 'application/json');
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

    private function canDeleteIncident($incidentId, $userId) {
      
        $sql = "SELECT id_incident FROM incidents WHERE id_incident = ? AND id_citoyen = ? AND statut = 'En attente'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$incidentId, $userId]);

        return $stmt->rowCount() > 0;
    }

    private function deleteIncident($incidentId) {
     
        $sql = "DELETE FROM incidents WHERE id_incident = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$incidentId]);

        return $stmt->rowCount() > 0;
    }
}
