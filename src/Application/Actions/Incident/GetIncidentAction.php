<?php

namespace App\Application\Actions\Incident;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

/**
 * @OA\Get(
 *     path="/infoIncident/{id}",
 *     tags={"Incidents"},
 *     summary="Récupère les détails d'un incident spécifique",
 *     description="Retourne les détails d'un incident basé sur son ID",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID de l'incident",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Détails de l'incident",
 *         @OA\JsonContent(ref="#/components/schemas/Incident")
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
 *             @OA\Property(property="erreur", type="string", example="Message d'erreur")
 *         )
 *     )
 * )
 */

class GetIncidentAction
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args): Response
    {
        $incidentId = intval($args['id']);

        $sql = "SELECT * FROM incidents WHERE id_incident = ?";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$incidentId]);

            $incident = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($incident) {
                $response->getBody()->write(json_encode($incident));
                return $response->withHeader('Content-Type', 'application/json');
            } else {
                $response->getBody()->write(json_encode([
                    'code' => 'NOT_FOUND',
                    'erreur' => 'Incident non trouvé.'
                ]));
                return $response->withStatus(404);
            }
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['code' => 'ERREUR', 'erreur' => $e->getMessage()]));
            return $response->withStatus(500);
        }
    }
}
