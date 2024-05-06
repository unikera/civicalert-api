<?php

namespace App\Application\Actions\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class ListAgentActionAdmin
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $stmt = $this->pdo->query('SELECT id_agent, nom, prenom, mail, pseudo, created_at, updated_at FROM agents'); // Sélectionnez uniquement les colonnes nécessaires
        $types = $stmt->fetchAll(PDO::FETCH_ASSOC); // Utilisez PDO::FETCH_ASSOC pour obtenir un tableau associatif
    
        $response->getBody()->write(json_encode($types));
        return $response->withHeader('Content-Type', 'application/json');
    }
}    
