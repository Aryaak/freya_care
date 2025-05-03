<?php
class Item {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getItems($filters = []) {
        $query = "
            SELECT items.*, stores.name AS store_name, categories.name AS category_name
            FROM items
            JOIN categories ON items.category_id = categories.id
            JOIN stores ON items.store_id = stores.id
            WHERE 1=1
        ";

        $params = [];

        // Apply filters
        if (!empty($filters['name'])) {
            $query .= " AND (items.name LIKE ? OR stores.name LIKE ?)";
            $name = '%' . $filters['name'] . '%';
            $params[] = $name;
            $params[] = $name;
        }

        if (!empty($filters['min_price'])) {
            $query .= " AND items.price >= ?";
            $params[] = $filters['min_price'];
        }

        if (!empty($filters['max_price'])) {
            $query .= " AND items.price <= ?";
            $params[] = $filters['max_price'];
        }

        if (!empty($filters['category'])) {
            $query .= " AND categories.id = ?";
            $params[] = $filters['category'];
        }

        $query .= " ORDER BY items.name ASC";

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getItemsByUserId($user_id) {
        $stmt = $this->pdo->prepare("SELECT items.*, categories.name as category_name 
            FROM items 
            JOIN categories ON items.category_id = categories.id 
            JOIN stores ON stores.id = items.store_id 
            WHERE stores.user_id = ?
            ORDER BY items.id DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createItem($store_id, $category_id, $image_path, $name, $description, $price) {
        $stmt = $this->pdo->prepare("INSERT INTO items (category_id, store_id, image, name, description, price) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([intval($category_id), $store_id, $image_path, $name, $description, $price]);
    }
}
