<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendInvoiceEmail($orderId, $pdo) {
    // Fetch order
    $stmt = $pdo->prepare("
        SELECT orders.*, users.name, users.email
        FROM orders
        JOIN users ON orders.user_id = users.id
        WHERE orders.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) return false;

    // Fetch items
    $itemStmt = $pdo->prepare("
        SELECT od.*, i.name, i.price
        FROM order_details od
        JOIN items i ON od.item_id = i.id
        WHERE od.order_id = ?
    ");
    $itemStmt->execute([$orderId]);
    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate PDF with TCPDF
    $pdf = new TCPDF();
    $pdf->AddPage();
    $html = "<h2>Invoice #{$order['id']}</h2>
    <p><strong>Name:</strong> {$order['name']}<br>
    <strong>Email:</strong> {$order['email']}<br>
    <strong>Status:</strong> {$order['status']}<br>
    <strong>Total:</strong> Rp. " . number_format($order['total_amount'], 0, '.', '.') . "</p><br>
    <table border='1' cellpadding='5'>
    <tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>";

    foreach ($items as $item) {
        $html .= "<tr>
            <td>{$item['name']}</td>
            <td>{$item['qty']}</td>
            <td>Rp. " . number_format($item['price'], 0, '.', '.') . "</td>
            <td>Rp. " . number_format($item['subtotal'], 0, '.', '.') . "</td>
        </tr>";
    }
    $html .= "</table>";
    $pdf->writeHTML($html);
    $tempPdf = sys_get_temp_dir() . "/invoice_{$order['id']}.pdf";
    $pdf->Output($tempPdf, 'F');

    // Send email with PHPMailer
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'autumnfreyfall@gmail.com';
        $mail->Password = 'gysa kpek izem fjhl';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('autumnfreyfall@gmail.com', 'Freya Care');
        $mail->addAddress($order['email'], $order['name']);
        $mail->Subject = "Invoice for Order #{$order['id']}";
        $mail->Body = "Dear {$order['name']},\n\nThank you! Please find your invoice attached.";
        $mail->addAttachment($tempPdf, "Invoice_{$order['id']}.pdf");
        $mail->send();
        unlink($tempPdf); // Clean up
        return true;
    } catch (Exception $e) {
        error_log("Email failed: {$mail->ErrorInfo}");
        return false;
    }
}
