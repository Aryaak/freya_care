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
$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    header("Location: ../index.php");
    exit;
}

// Fetch categories for dropdown
$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = $_POST['category_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    
    // Initialize with current image
    $image = $item['image'];
    
    // Handle new file upload if provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $destination = $uploadDir . $filename;
        
        // Check file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['image']['type'], $allowedTypes)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                // Delete old image if it exists
                if (!empty($item['image']) && file_exists('../../' . $item['image'])) {
                    unlink('../../' . $item['image']);
                }
                $image = 'uploads/' . $filename;
            } else {
                $error = "Failed to move uploaded file.";
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
        }
    }
    
    if (!isset($error)) {
        $stmt = $pdo->prepare("UPDATE items SET category_id = ?, image = ?, name = ?, description = ?, price = ? WHERE id = ?");
        $stmt->execute([$category_id, $image, $name, $description, $price, $id]);
        
        header("Location: index.php");
        exit;
    }
}
?>

<section class="container my-4 ">
    <h2>Edit Item</h2>
    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="category_id" class="form-label">Category</label>
            <select class="form-select" id="category_id" name="category_id" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $category): ?>
                <option value="<?= $category['id'] ?>" <?= $category['id'] == $item['category_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="image" class="form-label">Item Image</label>
            <?php if (!empty($item['image'])): ?>
            <div class="mb-2">
                <img src="../../<?= htmlspecialchars($item['image']) ?>" width="100" class="img-thumbnail">
                <div class="form-text">Current image</div>
            </div>
            <?php endif; ?>
            <input type="file" class="form-control" id="image" name="image" accept="image/jpeg, image/png, image/gif">
            <div class="form-text">Leave blank to keep current image</div>
        </div>
        <div class="mb-3">
            <label for="name" class="form-label">Item Name</label>
            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($item['name']) ?>"
                required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Item Description</label>
            <textarea name="description" id="description" class="form-control" required><?= htmlspecialchars($item['description']) ?></textarea>
        </div>
        <div class="mb-3">
            <label for="price" class="form-label">Price</label>
            <input type="number" class="form-control" id="price" name="price"
                value="<?= htmlspecialchars($item['price']) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </form>
</section>

<?php require_once('../../layouts/tail.php') ?>