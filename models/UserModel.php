<?php
namespace Models;

use PDO;

class UserModel {
    protected $db;
    protected $table = 'users';

    public function __construct(PDO $db) {
        $this->db = $db;
        error_log("=== UserModel initialisé ===");
        error_log("Table: " . $this->table);
        
        // Vérifier que la table users existe
        try {
            $stmt = $this->db->query("SHOW TABLES LIKE '{$this->table}'");
            if ($stmt->rowCount() === 0) {
                error_log("ERREUR: La table {$this->table} n'existe pas !");
            } else {
                error_log("Table {$this->table} trouvée");
                // Vérifier la structure de la table
                $stmt = $this->db->query("DESCRIBE {$this->table}");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                error_log("Colonnes: " . implode(", ", $columns));
            }
        } catch (\PDOException $e) {
            error_log("ERREUR lors de la vérification de la table: " . $e->getMessage());
        }
    }

    public function getUserByEmail(string $email) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function authenticate(string $email, string $password) {
        $user = $this->getUserByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return null;
    }

    public function register(string $email, string $password, string $username) {
        try {
            error_log("\n=== Tentative d'inscription ===");
            error_log("Email: " . $email);
            error_log("Username: " . $username);
            error_log("Password length: " . strlen($password));
            
            // Vérifier si l'email existe déjà
            $existingUser = $this->getUserByEmail($email);
            if ($existingUser) {
                error_log("ERREUR: Email déjà utilisé");
                return false;
            }
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            error_log("Mot de passe hashé créé (length: " . strlen($hashedPassword) . ")");
            
            $query = "INSERT INTO {$this->table} (email, password, username, created_at) VALUES (:email, :password, :username, NOW())";
            error_log("Query: " . $query);
            
            $stmt = $this->db->prepare($query);
            error_log("Requête préparée");
            
            $params = [
                'email' => $email,
                'password' => $hashedPassword,
                'username' => $username
            ];
            error_log("Paramètres: " . print_r($params, true));
            
            $result = $stmt->execute($params);
            
            if ($result) {
                error_log("Inscription réussie ! ID: " . $this->db->lastInsertId());
                return true;
            } else {
                error_log("Échec de l'exécution de la requête");
                error_log("Code erreur: " . implode(", ", $stmt->errorInfo()));
                return false;
            }
        } catch (\PDOException $e) {
            error_log("\nErreur PDO lors de l'inscription:");
            error_log("Message: " . $e->getMessage());
            error_log("Code: " . $e->getCode());
            error_log("Trace: " . $e->getTraceAsString());
            return false;
        } catch (\Exception $e) {
            error_log("\nErreur inattendue lors de l'inscription:");
            error_log("Message: " . $e->getMessage());
            error_log("Code: " . $e->getCode());
            error_log("Trace: " . $e->getTraceAsString());
            return false;
        }
    }
}
