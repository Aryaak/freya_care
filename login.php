<?php require_once 'layouts/head.php' ?>

<?php
require_once 'config/Database.php';
require_once 'config/Middleware.php';
require_once 'class/User.php';

// Check if the user is an guest
$middleware = new Middleware();
$middleware->guestOnly();

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $database = new Database();
    $pdo = $database->getConnection();
    
    $user = new User($pdo);

    try {
        $loggedInUser = $user->login($email, $password);

        if ($loggedInUser) {
            $user->storeLoginInfo($loggedInUser);
            header("Location: dashboard");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<section class="container mt-5" style="max-width: 500px;">
    <h3 class="mb-4 text-center">Login</h3>
    <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" action="">
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="text" name="email" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
        <div class="text-center mt-3">
            Don’t have an account? <a href="register.php">Register here</a>
        </div>
    </form>
</section>

<?php require_once 'layouts/tail.php' ?>
