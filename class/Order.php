<?php
class Order {
    private $pdo;
    private $userId;

    public function __construct($pdo, $userId = null) {
        $this->pdo = $pdo;
        $this->userId = $userId;
    }

    public function processCheckout($items, $paymentMethod) {
        $this->pdo->beginTransaction();
        try {
            $stores = [];

            foreach ($items as $item) {
                $storeId = $item['store_id'];
                if (!isset($stores[$storeId])) {
                    $stores[$storeId] = ['name' => $item['store_name'], 'items' => [], 'total' => 0];
                }
                $subtotal = $item['price'] * $item['qty'];
                $stores[$storeId]['items'][] = $item + ['subtotal' => $subtotal];
                $stores[$storeId]['total'] += $subtotal;
            }

            $orderIds = [];
            foreach ($stores as $storeId => $store) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO orders (user_id, store_id, payment, status, total_amount)
                    VALUES (?, ?, ?, 'process', ?)
                ");
                $stmt->execute([$this->userId, $storeId, $paymentMethod, $store['total']]);
                $orderId = $this->pdo->lastInsertId();
                $orderIds[] = $orderId;

                $stmtDetail = $this->pdo->prepare("
                    INSERT INTO order_details (order_id, item_id, qty, price, subtotal)
                    VALUES (?, ?, ?, ?, ?)
                ");
                foreach ($store['items'] as $item) {
                    $stmtDetail->execute([
                        $orderId, $item['item_id'], $item['qty'], $item['price'], $item['subtotal']
                    ]);
                }
            }

            $this->pdo->commit();
            return [true, $stores];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [false, $e->getMessage()];
        }
    }

    public function getOrdersByStoreId($store_id) {
        $stmt = $this->pdo->prepare("SELECT orders.*, users.name as user_name, users.address as user_address  
            FROM orders 
            JOIN users ON orders.user_id = users.id 
            WHERE store_id = ?
            ORDER BY orders.id DESC");
        $stmt->execute([$store_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
