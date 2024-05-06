<?php

namespace App\Application\Actions\Incident;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

/**
 * @OA\Get(
 *     path="/listeIncidents",
 *     tags={"Incidents"},
 *     summary="Récupère la liste de tous les incidents",
 *     description="Retourne une liste de tous les incidents enregistrés dans la base de données",
 *     @OA\Response(
 *         response=200,
 *         description="Liste des incidents récupérée avec succès",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(ref="#/components/schemas/Incident")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur",
 *         @OA\JsonContent(
 *             @OA\Property(property="error", type="string", example="Une erreur est survenue")
 *         )
 *     )
 * )
 */

class ListIncidentAction
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $stmt = $this->pdo->query('SELECT * FROM incidents'); // Sélectionnez uniquement les colonnes nécessaires
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC); // Utilisez PDO::FETCH_ASSOC pour obtenir un tableau associatif
    
        $response->getBody()->write(json_encode($types));
        return $response->withHeader('Content-Type', 'application/json');
    }
}    
