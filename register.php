<?php require_once 'layouts/head.php' ?>

<?php
require_once 'config/Database.php';
require_once 'config/Middleware.php';

$database = new Database();
$pdo = $database->getConnection();

$middleware = new Middleware();

$middleware->guestOnly();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = 'customer';

    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Email already registered.";
        } else {
            // Insert user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashedPassword, $role]);
            
            $user_id = $pdo->lastInsertId();

            // Set cookies (7 days)
            setcookie("user_id", $user_id, time() + (86400 * 7), "/");
            setcookie("user_name", $name, time() + (86400 * 7), "/");
            setcookie("user_role", $role, time() + (86400 * 7), "/");
            setcookie("user_address", null, time() + (86400 * 7), "/");

            header("Location: dashboard");
            exit;
        }
    } catch (PDOException $e) {
        $error = "Registration failed: " . $e->getMessage();
    }
}
?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="mb-4 text-center">Register</h2>
            <?php if (isset($message)) echo "<div class='alert alert-info'>$message</div>"; ?>
            <form method="POST" class="card card-body shadow-sm">
                <div class="mb-3">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Register</button>
                <div class="mt-2 text-center">
                    Already have an account? <a href="login.php">Login</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php require_once 'layouts/tail.php' ?>