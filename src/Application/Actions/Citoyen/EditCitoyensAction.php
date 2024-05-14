<?php

namespace App\Application\Actions\Citoyens;
use Slim\Psr7\Response;
use Slim\Psr7\Request;
use PDO;

class EditCitoyensAction {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, array $args): Response {
    
        $data = $request->getParsedBody();
    
        // Vérification des champs requis   
        $requiredFields = ['id', 'pseudo', 'nom', 'prenom', 'tel', 'mail'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === null) {
                $response->getBody()->write(json_encode(['code' => 'KO','message' => "Champ manquant : $field"]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        }
    
        $citoyenId = $data['id'];
        $pseudo = $data['pseudo'];
        $nom = $data['nom'];
        $prenom = $data['prenom'];
        $tel = $data['tel'];
        $mail = $data['mail'];
    
        // Vérification de l'existence du citoyen
        $stmt = $this->pdo->prepare('SELECT * FROM citoyens WHERE id_citoyen = :id');
        $stmt->bindParam(':id', $citoyenId);
        $stmt->execute();
        $existCitoyen = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$existCitoyen) {
            $response->getBody()->write(json_encode(['code' => 'KO','message' => "Citoyen non trouvé"]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    
        // Vérification du pseudo et de l'e-mail uniquement s'ils sont différents de ceux existants ou nouveaux pour d'autres citoyens
        $stmt = $this->pdo->prepare('SELECT id_citoyen FROM citoyens WHERE (pseudo = :pseudo OR mail = :mail) AND id_citoyen != :id');
        $stmt->bindParam(':pseudo', $pseudo);
        $stmt->bindParam(':mail', $mail);
        $stmt->bindParam(':id', $citoyenId);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $response->getBody()->write(json_encode(['code' => 'KO', 'message' => "Le pseudo ou l'e-mail est déjà utilisé"]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    
        // Mise à jour du citoyen
        $sql = "UPDATE citoyens SET pseudo = :pseudo, nom = :nom, prenom = :prenom, tel = :tel, mail = :mail WHERE id_citoyen = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':pseudo', $pseudo);
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':prenom', $prenom);
        $stmt->bindParam(':tel', $tel);
        $stmt->bindParam(':mail', $mail);
        $stmt->bindParam(':id', $citoyenId);
    
        $stmt->execute();
    
        $response->getBody()->write(json_encode(['code' => 'OK','message' => "Citoyen $citoyenId mis à jour avec succès"]));
    
        return $response->withHeader('Content-Type', 'application/json');
    }
    
}

?>
