<?php require_once "layouts/head.php" ?>

<?php
require_once('config/database.php');

// Redirect if not logged in
if (!isset($_COOKIE['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_COOKIE['user_id'];
$errors = [];
$success = '';

// Fetch current user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception("User not found");
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    try {
        // Validate inputs
        if (empty($name) || empty($email)) {
            throw new Exception("Name and email are required");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        // Check if email is being changed to one that already exists
        if ($email !== $user['email']) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception("Email already in use by another account");
            }
        }
        
        // Password change logic
        $password_changed = false;
        if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
            if (empty($current_password)) {
                throw new Exception("Current password is required to change password");
            }
            
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception("Current password is incorrect");
            }
            
            if (strlen($new_password) < 8) {
                throw new Exception("New password must be at least 8 characters");
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match");
            }
            
            $password_changed = true;
        }
        
        // Update user in database
        if ($password_changed) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, email = ?, address = ?, password = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $email, $address, $hashed_password, $user_id]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET name = ?, email = ?, address = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $email, $address, $user_id]);
        }
        
        // Update user data in session/cookie if needed
        setcookie("user_name", $name, time() + (86400 * 30), "/");
        setcookie("user_address", $address, time() + (86400 * 30), "/");
        
        $success = "Profile updated successfully!";
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}
?>

<?php require_once "layouts/header.php" ?>

<main role="main">
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <h5 class="my-3"><?= htmlspecialchars($user['name']) ?></h5>
                        <div class="d-flex justify-content-center mb-2">
                            <a href="<?= getDomainUrl() . '/orders' ?>" class="btn btn-primary">My Orders</a>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-3">
                                <p class="mb-0">Email</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0"><?= htmlspecialchars($user['email']) ?></p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-3">
                                <p class="mb-0">Address</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0">
                                    <?= !empty($user['address']) ? htmlspecialchars($user['address']) : 'Not set' ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Profile Settings</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>

                        <?php foreach ($errors as $error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endforeach; ?>

                        <form method="POST" action="profile.php">
                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <label for="name" class="form-label">Full Name</label>
                                </div>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="<?= htmlspecialchars($user['name']) ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <label for="email" class="form-label">Email</label>
                                </div>
                                <div class="col-sm-9">
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <label for="address" class="form-label">Address</label>
                                </div>
                                <div class="col-sm-9">
                                    <textarea class="form-control" id="address" name="address"
                                        rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <hr class="my-4">
                            <h6 class="mb-3">Change Password</h6>

                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                </div>
                                <div class="col-sm-9">
                                    <input type="password" autocomplete="off" value="" class="form-control" id="current_password"
                                        name="current_password">
                                    <small class="text-muted">Leave blank to keep current password</small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                </div>
                                <div class="col-sm-9">
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                    <small class="text-muted">At least 8 characters</small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-sm-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                </div>
                                <div class="col-sm-9">
                                    <input type="password" class="form-control" id="confirm_password"
                                        name="confirm_password">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-sm-3"></div>
                                <div class="col-sm-9">
                                    <button type="submit" class="btn btn-primary">Update Profile</button>
                                    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAccountModalLabel">Confirm Account Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete your account? This action cannot be undone.</p>
                <form id="deleteAccountForm" method="POST" action="delete_account.php">
                    <div class="mb-3">
                        <label for="deletePassword" class="form-label">Enter your password to confirm</label>
                        <input type="password" class="form-control" id="deletePassword" name="password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="deleteAccountForm" class="btn btn-danger">Delete Account</button>
            </div>
        </div>
    </div>
</div>

<?php require_once('layouts/tail.php'); ?>