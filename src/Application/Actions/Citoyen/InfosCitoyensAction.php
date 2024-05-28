<?php

namespace App\Application\Actions\Citoyen;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class InfosCitoyensAction {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, array $args): Response {
        $citoyenId = $args['id'] ?? null;

        if (!$citoyenId) {
            $responseBody = $response->getBody();
            $responseBody->write(json_encode(['error' => 'Citoyen ID is required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $stmt = $this->pdo->prepare("SELECT nom, prenom, pseudo, tel, mail FROM citoyens WHERE id_citoyen = :id");
        $stmt->bindParam(':id', $citoyenId);
        $stmt->execute();
        $citoyen = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$citoyen) {
            $responseBody = $response->getBody();
            $responseBody->write(json_encode(['error' => 'Citoyen not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $responseBody = $response->getBody();
        $responseBody->write(json_encode(['success' => 'Data retrieved successfully', 'data' => $citoyen]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

?>
