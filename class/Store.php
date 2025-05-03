<?php 

class Store {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getStoreByUserId($user_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM stores WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}