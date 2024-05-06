<?php

namespace App\Application\Actions\Admin;
use PDOException;
use PDO;

class AdminService {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function authenticate($email, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM admin WHERE mail = :email LIMIT 1");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                unset($user['mdp']); 
                return $user;
            } else {
                return null;
            }
        } catch (PDOException $e) {
            throw $e;
        }
    }
}
