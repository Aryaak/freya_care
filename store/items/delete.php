<?php
require_once '../../config/Database.php';
require_once '../../config/Middleware.php';

$database = new Database();
$pdo = $database->getConnection();

$middleware = new Middleware();
$middleware->requireAuth();

if (isset($_GET['id'])) {
    // First get the image path
    $stmt = $pdo->prepare("SELECT image FROM items WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $item = $stmt->fetch();
    
    // Delete the record
    $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    
    // Delete the image file if it exists
    if ($item && !empty($item['image']) && file_exists('../../' . $item['image'])) {
        unlink('../../' . $item['image']);
    }
}

header("Location: ../index.php");
exit;
?>