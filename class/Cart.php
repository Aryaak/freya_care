<?php
class Cart {
    private $pdo;
    private $user_id;   

    public function __construct($pdo, $user_id) {
        $this->pdo = $pdo;
        $this->user_id = $user_id;
    }

    public function addItem($item_id) {
        // Check if the user already has a cart
        $stmt = $this->pdo->prepare("SELECT id FROM carts WHERE user_id = ?");
        $stmt->execute([$this->user_id]);
        $cart = $stmt->fetch();

        if (!$cart) {
            $stmt = $this->pdo->prepare("INSERT INTO carts (user_id) VALUES (?)");
            $stmt->execute([$this->user_id]);
            $cart_id = $this->pdo->lastInsertId();
        } else {
            $cart_id = $cart['id'];
        }

        // Check if the item is already in the cart
        $stmt = $this->pdo->prepare("SELECT id, qty FROM cart_details WHERE cart_id = ? AND item_id = ?");
        $stmt->execute([$cart_id, $item_id]);
        $cart_item = $stmt->fetch();

        if ($cart_item) {
            // Update quantity if item exists
            $new_qty = $cart_item['qty'] + 1;
            $stmt = $this->pdo->prepare("UPDATE cart_details SET qty = ? WHERE id = ?");
            $stmt->execute([$new_qty, $cart_item['id']]);
        } else {
            // Add item to cart
            $stmt = $this->pdo->prepare("INSERT INTO cart_details (cart_id, item_id, qty) VALUES (?, ?, 1)");
            $stmt->execute([$cart_id, $item_id]);
        }
    }

    public function updateQuantity($cart_detail_id, $new_qty) {
        if ($new_qty < 1) {
            throw new Exception("Quantity must be at least 1");
        }

        // Update quantity in database
        $stmt = $this->pdo->prepare("UPDATE cart_details SET qty = ? WHERE id = ?");
        $stmt->execute([$new_qty, $cart_detail_id]);
        
        return $this->getCartSummary();
    }

    public function removeItem($cart_detail_id) {
        // Remove item from cart
        $stmt = $this->pdo->prepare("DELETE FROM cart_details WHERE id = ?");
        $stmt->execute([$cart_detail_id]);

        return $this->getCartSummary();
    }

    public function getCartItems() {
        $stmt = $this->pdo->prepare("
            SELECT 
                cd.id, 
                cd.qty, 
                i.id as item_id,
                i.name, 
                i.price, 
                i.image, 
                s.name as store_name
            FROM cart_details cd
            JOIN items i ON cd.item_id = i.id
            JOIN stores s ON i.store_id = s.id
            JOIN carts c ON cd.cart_id = c.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCartSummary() {
        $stmt = $this->pdo->prepare("
            SELECT 
                SUM(i.price * cd.qty) as total, 
                COUNT(*) as item_count,
                SUM(cd.qty) as total_items
            FROM cart_details cd
            JOIN items i ON cd.item_id = i.id
            JOIN carts c ON cd.cart_id = c.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$this->user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
