<?php

namespace App\Application\Actions\Incident;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use PDO;

/**
 * @OA\Post(
 *     path="/declarerIncident",
 *     tags={"Incidents"},
 *     summary="Déclare un nouvel incident",
 *     description="Permet à un utilisateur authentifié de déclarer un nouvel incident",
 *     @OA\RequestBody(
 *         required=true,
 *         description="Données de l'incident à déclarer",
 *         @OA\JsonContent(
 *             required={"titre", "description", "latitude", "longitude", "idType"},
 *             @OA\Property(property="titre", type="string", example="Panne de lumière"),
 *             @OA\Property(property="description", type="string", example="Les lumières de la rue sont toutes éteintes"),
 *             @OA\Property(property="latitude", type="number", format="float", example=48.8566),
 *             @OA\Property(property="longitude", type="number", format="float", example=2.3522),
 *             @OA\Property(property="idType", type="integer", example=1),
 *             @OA\Property(property="photo", type="string", example="data:image/png;base64,...")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Incident déclaré avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="idIncident", type="integer", example=123)
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Non autorisé",
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="string", example="UNAUTHORIZED"),
 *             @OA\Property(property="erreur", type="string", example="Vous devez être connecté pour déclarer un incident.")
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


class DeclareIncidentAction
{
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response): Response {
        $data = $request->getParsedBody();
        $userId = $this->getUserIdFromToken($request);
    
        if (!$userId) {
            $response->getBody()->write(json_encode([
                'code' => 'UNAUTHORIZED',
                'erreur' => 'Vous devez être connecté pour déclarer un incident.'
            ]));
            return $response->withStatus(401);
        }
    
        $titre = $data['titre'] ?? '';
        $description = $data['description'] ?? '';
        $latitude = $data['latitude'] ?? 0;
        $longitude = $data['longitude'] ?? 0;
        $idType = $data['idType'] ?? 0;
        $photo = $data['photo'] ?? '';
    
        $sql = "INSERT INTO incidents (titre, description, latitude, longitude, id_citoyen, id_type, photo, statut)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$titre, $description, $latitude, $longitude, $userId, $idType, $photo, 'En attente']);
    
            $incidentId = $this->pdo->lastInsertId();
    
            $response->getBody()->write(json_encode(['idIncident' => $incidentId]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['code' => 'ERREUR', 'erreur' => $e->getMessage()]));
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
}
