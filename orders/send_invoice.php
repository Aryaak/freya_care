<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
function sendInvoiceEmail($orderId, $pdo) {
    // Fetch order
    $stmt = $pdo->prepare("
        SELECT orders.*, users.name, users.email, users.address
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
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Freya Care');
    $pdf->SetAuthor('Freya Care');
    $pdf->SetTitle('Invoice #' . $order['id']);
    $pdf->SetMargins(15, 25, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(TRUE, 25);
    $pdf->AddPage();

    // Set colors
    $primaryColor = '#3F7373';
    $secondaryColor = '#C5D7D9';
    $textColor = '#333333';

    // Build HTML content
    $html = '
    <div style="text-align:center; margin-bottom:20px;">
        <h1 style="color:'.$primaryColor.'; font-size:24px; font-weight:bold; margin-bottom:5px;">FREYA CARE</h1>
        <p style="color:'.$textColor.'; font-size:12px;">Surabaya, Indonesia | Phone: +62 812-1736-6228</p>
    </div>

    <div style="background-color:'.$primaryColor.'; color:#ffffff; padding:10px; text-align:center; margin-bottom:20px;">
        <h2 style="margin:0; font-size:18px;">INVOICE #'.$order['id'].'</h2>
    </div>

    <table style="width:100%; margin-bottom:20px;">
        <tr>
            <td style="width:50%; vertical-align:top;">
                <h3 style="color:'.$primaryColor.'; font-size:14px; border-bottom:1px solid '.$primaryColor.'; padding-bottom:5px; margin-bottom:10px;">BILL TO</h3>
                <p style="font-weight:bold; margin:0;">'.htmlspecialchars($order['name']).'</p>
                <p style="margin:0;">'.htmlspecialchars($order['address']).'</p>
                <p style="margin:0;">'.htmlspecialchars($order['email']).'</p>
            </td>
            <td style="width:50%; vertical-align:top; text-align:right;">
                <h3 style="color:'.$primaryColor.'; font-size:14px; border-bottom:1px solid '.$primaryColor.'; padding-bottom:5px; margin-bottom:10px;">INVOICE DETAILS</h3>
                <table style="width:100%;">
                    <tr>
                        <td style="text-align:left; width:40%; font-weight:bold;">Invoice #</td>
                        <td style="text-align:left;">'.$order['id'].'</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; font-weight:bold;">Date</td>
                        <td style="text-align:left;">'.date('F j, Y').'</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; font-weight:bold;">Status</td>
                        <td style="text-align:left; color:'.($order['status'] == 'done' ? '#4CAF50' : '#FF9800').';">'.$order['status'].'</td>
                    </tr>
                    <tr>
                        <td style="text-align:left; font-weight:bold;">Peyment</td>
                        <td style="text-align:left;">'.$order['payment'].'</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <h3 style="color:'.$primaryColor.'; font-size:14px; border-bottom:1px solid '.$primaryColor.'; padding-bottom:5px; margin-bottom:10px;">ORDER ITEMS</h3>
    <table style="width:100%; border-collapse:collapse; margin-bottom:20px;">
        <thead>
            <tr style="background-color:'.$secondaryColor.';">
                <th style="border:1px solid #dddddd; padding:8px; text-align:left; font-weight:bold;">Item</th>
                <th style="border:1px solid #dddddd; padding:8px; text-align:center; font-weight:bold;">Qty</th>
                <th style="border:1px solid #dddddd; padding:8px; text-align:right; font-weight:bold;">Unit Price</th>
                <th style="border:1px solid #dddddd; padding:8px; text-align:right; font-weight:bold;">Subtotal</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($items as $item) {
        $html .= '
        <tr>
            <td style="border:1px solid #dddddd; padding:8px; text-align:left;">'.htmlspecialchars($item['name']).'</td>
            <td style="border:1px solid #dddddd; padding:8px; text-align:center;">'.$item['qty'].'</td>
            <td style="border:1px solid #dddddd; padding:8px; text-align:right;">Rp. '.number_format($item['price'], 0, ',', '.').'</td>
            <td style="border:1px solid #dddddd; padding:8px; text-align:right;">Rp. '.number_format($item['subtotal'], 0, ',', '.').'</td>
        </tr>';
    }

    $html .= '
        </tbody>
    </table>

    <table style="width:100%; margin-bottom:30px;">
        <tr>
            <td style="width:70%;"></td>
            <td style="width:30%;">
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td style="border:1px solid #dddddd; padding:8px; text-align:right; font-weight:bold; background-color:'.$secondaryColor.';">Subtotal</td>
                        <td style="border:1px solid #dddddd; padding:8px; text-align:right;">Rp. '.number_format($order['total_amount'], 0, ',', '.').'</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #dddddd; padding:8px; text-align:right; font-weight:bold; background-color:'.$secondaryColor.';">Tax (0%)</td>
                        <td style="border:1px solid #dddddd; padding:8px; text-align:right;">Rp. 0</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #dddddd; padding:8px; text-align:right; font-weight:bold; background-color:'.$primaryColor.'; color:#ffffff;">Total</td>
                        <td style="border:1px solid #dddddd; padding:8px; text-align:right; font-weight:bold;">Rp. '.number_format($order['total_amount'], 0, ',', '.').'</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div style="border-top:1px solid #eeeeee; padding-top:10px; font-size:10px; color:#777777;">
        <p style="text-align:center;">Thank you for your business!</p>
        <p style="text-align:center;">If you have any questions about this invoice, please contact<br>support@freyacare.com or call +62 812-1736-6228</p>
    </div>';

    $pdf->writeHTML($html, true, false, true, false, '');
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
        $mail->Body = "Dear {$order['name']},\n\nThank you for your order! Please find your invoice attached.\n\nBest regards,\nFreya Care Team";
        $mail->addAttachment($tempPdf, "Invoice_{$order['id']}.pdf");
        $mail->send();
        unlink($tempPdf);
        return true;
    } catch (Exception $e) {
        error_log("Email failed: {$mail->ErrorInfo}");
        if (file_exists($tempPdf)) {
            unlink($tempPdf);
        }
        return false;
    }
}
