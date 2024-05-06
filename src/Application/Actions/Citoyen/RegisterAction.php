<?php

namespace App\Application\Actions\Citoyen;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

/**
 * @OA\Post(
 *     path="/inscription",
 *     tags={"Citoyen"},
 *     summary="Inscription d'un nouveau citoyen",
 *     description="Permet à un nouveau citoyen de s'inscrire dans le système",
 *     @OA\RequestBody(
 *         required=true,
 *         description="Informations nécessaires pour l'inscription du citoyen",
 *         @OA\JsonContent(
 *             required={"pseudo", "mail", "mdp", "nom", "prenom", "tel"},
 *             @OA\Property(property="pseudo", type="string", example="citoyenPseudo"),
 *             @OA\Property(property="mail", type="string", example="citoyen@example.com"),
 *             @OA\Property(property="mdp", type="string", example="motdepasse"),
 *             @OA\Property(property="nom", type="string", example="Doe"),
 *             @OA\Property(property="prenom", type="string", example="John"),
 *             @OA\Property(property="tel", type="string", example="0123456789")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Citoyen inscrit avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="idCitoyen", type="integer", example=123)
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Mauvaise requête",
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="string", example="ERREUR"),
 *             @OA\Property(property="erreur", type="string", example="Ce pseudo est déjà pris ou Cette adresse e-mail est déjà enregistrée")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur",
 *         @OA\JsonContent(
 *             @OA\Property(property="code", type="string", example="ERREUR"),
 *             @OA\Property(property="erreur", type="string", example="Échec de l'inscription")
 *         )
 *     )
 * )
 */

 
class RegisterAction
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $data = $request->getParsedBody();
        $pseudo = $data['pseudo'];
        $mail = $data['mail'];

        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS count FROM citoyens WHERE pseudo = ?");
        $stmt->execute([$pseudo]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            $response->getBody()->write(json_encode(['code' => 'ERREUR', 'erreur' => 'Ce pseudo est déjà pris']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS count FROM citoyens WHERE mail = ?");
        $stmt->execute([$mail]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            $response->getBody()->write(json_encode(['code' => 'ERREUR', 'erreur' => 'Cette adresse e-mail est déjà enregistrée']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $passwordHash = password_hash($data['mdp'], PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO citoyens (nom, prenom, mail, tel, pseudo, mdp) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$data['nom'], $data['prenom'], $mail, $data['tel'], $pseudo, $passwordHash]);

        if ($stmt->rowCount() > 0) {
            $citoyenId = $this->pdo->lastInsertId();
            $response->getBody()->write(json_encode(['idCitoyen' => $citoyenId]));
        } else {
            $response->getBody()->write(json_encode(['code' => 'ERREUR', 'erreur' => 'Échec de l\'inscription']));
        }

        return $response->withHeader('Content-Type', 'application/json');

        
    }
}
