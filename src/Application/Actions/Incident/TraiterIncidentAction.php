<?php

namespace App\Application\Actions\Incident;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PDO;
/**
 * @OA\Put(
 *     path="/traiterIncident",
 *     tags={"Incidents"},
 *     summary="Traite un incident",
 *     description="Permet à un agent d'assigner un incident à lui-même pour traitement",
 *     @OA\RequestBody(
 *         required=true,
 *         description="Identifiants de l'incident et de l'agent",
 *         @OA\JsonContent(
 *             required={"idIncident", "idAgent"},
 *             @OA\Property(property="idIncident", type="integer", example=123),
 *             @OA\Property(property="idAgent", type="integer", example=456)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Incident traité avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="statuts", type="string", example="ok")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Mauvaise requête",
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="string", example="ERREUR"),
 *             @OA\Property(property="erreur", type="string", example="Incident déjà pris en charge ou non existant")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur",
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="string", example="ERREUR"),
 *             @OA\Property(property="erreur", type="string", example="Erreur lors du traitement de l'incident")
 *         )
 *     )
 * )
 */


class TraiterIncidentAction
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response,$args): Response
    {
        $data = $request->getParsedBody();
        $idIncident =intval($args['id']); 
        $userId = $this->getUserIdFromToken($request);
        $idAgent = $userId; 

        if (!$userId) {
            $response->getBody()->write(json_encode(['ERROR' => 'Vous devez être connecté']));
            return $response->withStatus(401);
        }

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("UPDATE incidents SET id_agent = ?, statut = 'En cours' WHERE id_incident = ? AND id_agent IS NULL");
            $stmt->execute([$idAgent, $idIncident]);

            if ($stmt->rowCount() > 0) {
                $this->pdo->commit();
                $response->getBody()->write(json_encode(['statuts' => 'ok']));
            } else {
                $this->pdo->rollBack();
                $response->getBody()->write(json_encode(['code' => 'ERREUR', 'erreur' => 'Incident déjà pris en charge ou non existant']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            $response->getBody()->write(json_encode(['code' => 'ERREUR', 'erreur' => 'Erreur lors du traitement de l\'incident']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
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

}
