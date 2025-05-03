<?php
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function login($email, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, name, address, password, role FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                return $user;
            }
            return null;
        } catch (PDOException $e) {
            throw new Exception("Database error: " . $e->getMessage());
        }
    }

    public function storeLoginInfo($user) {
        setcookie("user_id", $user['id'], time() + (86400 * 7), "/");
        setcookie("user_name", $user['name'], time() + (86400 * 7), "/");
        setcookie("user_role", $user['role'], time() + (86400 * 7), "/");
        setcookie("user_address", $user['address'], time() + (86400 * 7), "/");
    }

    public function isLoggedIn() {
        return isset($_COOKIE['user_id']);
    }

    public function getUserId() {
        return $_COOKIE['user_id'] ?? null;
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
