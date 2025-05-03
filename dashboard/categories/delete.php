<?php
require_once '../../config/Database.php';
require_once '../../config/Middleware.php';
require_once '../../class/Category.php';

$database = new Database();
$pdo = $database->getConnection();

$middleware = new Middleware();
$middleware->requireAuth();

$category = new Category($pdo);

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $category->delete($id);
}

header("Location: index.php");
exit;
