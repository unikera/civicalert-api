<?php

namespace App\Application\Actions\Citoyens;
use Slim\Psr7\Response;
use Slim\Psr7\Request;
use PDO;

class EditPassAction {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, array $args): Response {
    
        $data = $request->getParsedBody();
    
        // Vérification des champs requis   
        $requiredFields = ['id', 'expass', 'newpass'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $response->getBody()->write(json_encode(['code' => 'KO','message' => "Champ manquant : $field"]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        }
    
        $citoyenId = $data['id'];
        $expass = $data['expass'];
        $newpass = $data['newpass'];
    
        // Vérification de l'existence du citoyen et de l'ancien mot de passe
        $stmt = $this->pdo->prepare('SELECT mdp FROM citoyens WHERE id_citoyen = :id');
        $stmt->bindParam(':id', $citoyenId);
        $stmt->execute();
        $citoyen = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$citoyen) {
            $response->getBody()->write(json_encode(['code' => 'KO','message' => "Citoyen non trouvé"]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        if (!password_verify($expass, $citoyen['mdp'])) {
            $response->getBody()->write(json_encode(['code' => 'KO','message' => "Le mot de passe actuel est incorrect"]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        // Mise à jour du mot de passe
        $newpassHash = password_hash($newpass, PASSWORD_DEFAULT); // Hash du nouveau mot de passe
        $sql = "UPDATE citoyens SET mdp = :newpass WHERE id_citoyen = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':newpass', $newpassHash);
        $stmt->bindParam(':id', $citoyenId);
        $stmt->execute();
    
        $response->getBody()->write(json_encode(['code' => 'OK','message' => "Mot de passe mis à jour avec succès"]));
    
        return $response->withHeader('Content-Type', 'application/json');
    }
}
?>