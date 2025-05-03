<?php require_once('../../layouts/head.php') ?>

<?php require_once('../../layouts/navbar.php') ?>

<?php
require_once '../../config/Database.php';
require_once '../../config/Middleware.php';

$database = new Database();
$pdo = $database->getConnection();

$middleware = new Middleware();
$middleware->requireAuth();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?  ORDER BY id DESC");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    
    header("Location: index.php");
    exit;
}
?>

<section class="container my-4">
    <h2>Edit Order</h2>
    <form method="POST">
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">Select Status</option>
                <option value="process" <?= $order['status'] == 'process' ? 'selected' : '' ?>>Process</option>
                <option value="deliver" <?= $order['status'] == 'deliver' ? 'selected' : '' ?>>Deliver</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</section>

<?php require_once('../../layouts/tail.php') ?>