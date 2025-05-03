<?php
require_once '../layouts/head.php';
require_once '../layouts/header.php';
require_once '../config/Database.php';
require_once '../config/Middleware.php';
require_once 'send_invoice.php';

$database = new Database();
$pdo = $database->getConnection();

$middleware = new Middleware();
$middleware->requireAuth();

$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    die("Order ID is missing.");
}

// Fetch order details
$stmt = $pdo->prepare("
    SELECT orders.*, stores.user_id AS store_user_id, users.name AS user_name, users.address AS user_address
    FROM orders
    JOIN users ON orders.user_id = users.id
    JOIN stores ON orders.store_id = stores.id
    WHERE orders.id = ?
    ORDER BY id DESC
");

$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order not found or access denied.");
}

// Fetch order items
$itemStmt = $pdo->prepare("
    SELECT od.*, i.name, i.price
    FROM order_details od
    JOIN items i ON od.item_id = i.id
    WHERE od.order_id = ?
");

$itemStmt->execute([$orderId]);
$orderItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle status changes
    if (isset($_POST['action'])) {
        $id = $_POST['id'];
        $action = $_POST['action'];
        
        if ($action === 'cancel') {
            $reason = $_POST['reason'] ?? '';
            if (empty($reason)) {
                $_SESSION['error'] = "Please provide a cancellation reason.";
                header("Location: detail.php?id=" . $id);
                exit();
            }
            
            $stmt = $pdo->prepare("
                UPDATE orders SET status = ?, cancel_reason = ? WHERE id = ?
            ");
            $stmt->execute([$action, $reason, $id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE orders SET status = ? WHERE id = ?
            ");
            $stmt->execute([$action, $id]);
        }

        if ($action === 'done') {
            sendInvoiceEmail($id, $pdo);
        }
        
        header("Location: detail.php?id=" . $id);
        exit();
    }
    
    // Handle feedback submission
    if (isset($_POST['feedback'])) {
        $order_detail_id = $_POST['order_detail_id'];
        $feedback = $_POST['feedback'];
        
        $stmt = $pdo->prepare("
            UPDATE order_details SET feedback = ? WHERE id = ?
        ");
        $stmt->execute([$feedback, $order_detail_id]);
        
        header("Location: detail.php?id=" . $orderId);
        exit();
    }
}
?>

<?php 
require '../vendor/autoload.php'; // Make sure to include the Dompdf autoload file

use Dompdf\Dompdf;

function generateInvoicePDF($order, $orderItems) {
    $dompdf = new Dompdf();
    
    // Create HTML content for the PDF
    $html = '<h1>Invoice for Order #' . htmlspecialchars($order['id']) . '</h1>';
    $html .= '<p><strong>Name:</strong> ' . htmlspecialchars($order['user_name']) . '</p>';
    $html .= '<p><strong>Address:</strong> ' . htmlspecialchars($order['user_address']) . '</p>';
    $html .= '<p><strong>Total:</strong> Rp. ' . number_format($order['total_amount'], 0, '.', '.') . '</p>';
    $html .= '<h4>Items</h4><table><tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>';
    
    foreach ($orderItems as $item) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($item['name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['qty']) . '</td>';
        $html .= '<td>Rp. ' . number_format($item['price'], 0, '.', '.') . '</td>';
        $html .= '<td>Rp. ' . number_format($item['subtotal'], 0, '.', '.') . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    
    // Load HTML content
    $dompdf->loadHtml($html);
    
    // Set paper size and orientation
    $dompdf->setPaper('A4', 'portrait');
    
    // Render the PDF
    $dompdf->render();
    
    // Output the generated PDF to Browser
    $pdfOutput = $dompdf->output();
    $pdfFilePath = '../invoices/invoice_' . $order['id'] . '.pdf';
    file_put_contents($pdfFilePath, $pdfOutput);
    
    return $pdfFilePath;
}
?>

<section class="container my-4">
    <h2>Order #<?= htmlspecialchars($order['id']) ?> Details</h2>
    <p><strong>Name:</strong> <?= htmlspecialchars($order['user_name']) ?></p>
    <p><strong>Address:</strong> <?= htmlspecialchars($order['user_address']) ?></p>
    <p><strong>Total:</strong> Rp. <?= number_format($order['total_amount'], 0, '.', '.') ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>
    <p><strong>Payment:</strong> <?= htmlspecialchars($order['payment']) ?></p>

    <?php if ($order['status'] === 'cancel' && !empty($order['cancel_reason'])): ?>
    <p><strong>Cancellation Reason:</strong> <?= htmlspecialchars($order['cancel_reason']) ?></p>
    <?php endif; ?>

    <h4 class="mt-4">Items</h4>
    <table class="table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orderItems as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= htmlspecialchars($item['qty']) ?></td>
                <td>Rp. <?= number_format($item['price'], 0, '.', '.') ?></td>
                <td>Rp. <?= number_format($item['subtotal'], 0, '.', '.') ?></td>
            </tr>
            <?php if($order['status'] == 'done' && $order['user_id'] == $_COOKIE['user_id']): ?>
            <tr>
                <td colspan="4">
                    <form action="" method="POST">
                        <input type="hidden" name="order_detail_id" value="<?= $item['id'] ?>">
                        <div class="mb-3">
                            <label for="feedback_<?= $item['id'] ?>" class="form-label">Your Feedback</label>
                            <textarea name="feedback" id="feedback_<?= $item['id'] ?>" class="form-control"><?= 
                                htmlspecialchars($item['feedback'] ?? '') 
                            ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3 w-100">
                            <?= !empty($item['feedback']) ? 'Update Feedback' : 'Submit Feedback' ?>
                        </button>
                    </form>
                </td>
            </tr>
            <?php elseif(!empty($item['feedback'])): ?>
            <tr>
                <td colspan="4">
                    <div class="card p-3">
                        <strong>Feedback:</strong>
                        <p><?= htmlspecialchars($item['feedback']) ?></p>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <form action="" method="POST">
        <input type="hidden" name="id" value="<?= $order['id'] ?>">

        <?php if($order['store_user_id'] == $_COOKIE['user_id'] && $order['status'] == 'process'): ?>
        <input type="hidden" name="action" value="deliver">
        <button class="btn btn-warning text-light w-100" type="submit">Deliver Order</button>
        <?php elseif($order['status'] == 'deliver' && $order['user_id'] == $_COOKIE['user_id']): ?>
        <input type="hidden" name="action" value="done">
        <button class="btn btn-success text-light w-100" type="submit">Order Done</button>
        <?php elseif(($order['status'] == 'process' || $order['status'] == 'pending') && $order['user_id'] == $_COOKIE['user_id']): ?>
        <input type="hidden" name="action" value="cancel">
        <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#cancelModal">
            Cancel Order
        </button>
        <?php endif; ?>
    </form>
</section>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelModalLabel">Cancel Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="action" value="cancel">
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for cancellation</label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../layouts/tail.php'; ?>