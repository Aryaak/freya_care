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
$stmt = $pdo->prepare("SELECT * FROM stores WHERE id = ?");
$stmt->execute([$id]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    header("Location: index.php");
    exit;
}

// Fetch users for dropdown
$users = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $status = $_POST['status'];
    $reject_reason = $_POST['reject_reason'] ?? null;
    
    // Clear reject reason if status is not 'reject'
    if ($status !== 'reject') {
        $reject_reason = null;
    }
    
    $stmt = $pdo->prepare("UPDATE stores SET name = ?, status = ?, reject_reason = ? WHERE id = ?");
    $stmt->execute([$name, $status, $reject_reason, $id]);
    
    header("Location: index.php");
    exit;
}
?>

<section class="container mt-4">
    <h2>Edit Store</h2>
    <form method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Store Name</label>
            <input type="text" class="form-control" id="name" name="name"
                value="<?= htmlspecialchars($store['name']) ?>" required readonly>
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status" required onchange="toggleRejectReason(this.value)">
                <option value="pending" <?= $store['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="accept" <?= $store['status'] == 'accept' ? 'selected' : '' ?>>Accept</option>
                <option value="reject" <?= $store['status'] == 'reject' ? 'selected' : '' ?>>Reject</option>
            </select>
        </div>
        <div class="mb-3" id="rejectReasonContainer"
            style="display: <?= $store['status'] == 'reject' ? 'block' : 'none' ?>;">
            <label for="reject_reason" class="form-label">Reject Reason</label>
            <textarea class="form-control" id="reject_reason" name="reject_reason"
                rows="3"><?= htmlspecialchars($store['reject_reason'] ?? '') ?></textarea>
            <div class="form-text">Please provide a clear reason for rejection</div>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</section>

<script>
    function toggleRejectReason(status) {
        const container = document.getElementById('rejectReasonContainer');
        if (status === 'reject') {
            container.style.display = 'block';
            document.getElementById('reject_reason').setAttribute('required', '');
        } else {
            container.style.display = 'none';
            document.getElementById('reject_reason').removeAttribute('required');
        }
    }
</script>

<?php require_once('../../layouts/tail.php') ?>