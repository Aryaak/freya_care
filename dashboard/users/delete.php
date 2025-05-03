<?php
require_once '../../config/Database.php';
require_once '../../config/Middleware.php';
require_once '../../class/User.php';

$database = new Database();
$pdo = $database->getConnection();

$middleware = new Middleware();
$middleware->requireAuth();

$user = new User($pdo);

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $user->delete($id);
}

header("Location: index.php");
exit;
