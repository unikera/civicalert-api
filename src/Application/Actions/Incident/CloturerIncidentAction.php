<?php

namespace App\Application\Actions\Incident;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PDO;

 class CloturerIncidentAction
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

        if (!$this->canUpdateIncident($incidentId, $userId)) {
            $response->getBody()->write(json_encode(['ERROR' => 'Vous ne pouvez pas cloturé cet incident']));
            return $response->withStatus(403);
        }

        if ($this->updateIncident($incidentId, $userId)) {
            $response->getBody()->write(json_encode(['SUCESS ' => 'Incident cloturé avec succès.']));
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

    private function canUpdateIncident($incidentId, $userId) {
      
        $sql = "SELECT id_incident FROM incidents WHERE id_incident = ? AND id_agent = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$incidentId, $userId]);

        return $stmt->rowCount() > 0;
    }

    private function updateIncident($incidentId,  $userId) {
     
        $sql = "UPDATE incidents SET statut = 'Clôturé' WHERE id_incident = ? AND id_agent = ?; ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$incidentId, $userId]);

        return $stmt->rowCount() > 0;
    }
}
