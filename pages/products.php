
<?php
require_once "../config/db.php";

$message = "";
$error = "";

// Process Delete Product form
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_product"])) {

    $product_id = trim($_POST["product_id"]);

    if ($product_id !== "") {

        $conn->begin_transaction();

        try {

            // First delete related inventory batches
            $stmt = $conn->prepare(
                "DELETE FROM inventory_batches WHERE product_id = ?"
            );

            $stmt->bind_param("s", $product_id);

            if (!$stmt->execute()) {
                throw new Exception("Could not delete inventory batches.");
            }

            $stmt->close();

            // Then delete the product
            $stmt = $conn->prepare(
                "DELETE FROM products WHERE product_id = ?"
            );

            $stmt->bind_param("s", $product_id);

            if (!$stmt->execute()) {
                throw new Exception("Could not delete product.");
            }

            if ($stmt->affected_rows === 0) {
                throw new Exception("Product not found.");
            }

            $stmt->close();

            $conn->commit();

            $message = "Product deleted successfully!";

        } catch (Exception $e) {

            $conn->rollback();

            if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                $stmt->close();
            }

            $error = "Could not delete product. It may be linked to existing orders.";
        }
    }
}


// Process Add Product form
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_product"])) {

    $product_id = trim($_POST["product_id"]);
    $product_name = trim($_POST["product_name"]);
    $category = trim($_POST["category"]);
    $unit_price = (float) $_POST["unit_price"];
    $cost_price = (float) $_POST["cost_price"];
    $stock_quantity = (int) $_POST["stock_quantity"];
    $expiry_date = $_POST["expiry_date"];

    // Validate form data
    $date = DateTime::createFromFormat('!Y-m-d', $expiry_date);

    if (
        $product_id === "" ||
        $product_name === "" ||
        $category === "" ||
        $unit_price < 0 ||
        $cost_price < 0 ||
        $stock_quantity < 0 ||
        !$date ||
        $date->format('Y-m-d') !== $expiry_date
    ) {

        $error = "Please enter valid product information.";

    } else {

        // Start database transaction
        $conn->begin_transaction();

        try {

            // Insert product into products table
            $stmt = $conn->prepare(
                "INSERT INTO products
                (product_id, product_name, category, unit_price,
                 cost_price, stock_quantity, expiry_date)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "sssddis",
                $product_id,
                $product_name,
                $category,
                $unit_price,
                $cost_price,
                $stock_quantity,
                $expiry_date
            );

            if (!$stmt->execute()) {
                throw new Exception("Product insertion failed.");
            }

            $stmt->close();

            // Insert initial stock into inventory_batches
            if ($stock_quantity > 0) {

                $batch = $conn->prepare(
                    "INSERT INTO inventory_batches
                    (product_id, quantity_received, quantity_remaining,
                     received_date, expiry_date)
                    VALUES (?, ?, ?, CURDATE(), ?)"
                );

                $batch->bind_param(
                    "siis",
                    $product_id,
                    $stock_quantity,
                    $stock_quantity,
                    $expiry_date
                );

                if (!$batch->execute()) {
                    throw new Exception("Inventory batch insertion failed.");
                }

                $batch->close();
            }

            // Save both changes
            $conn->commit();

            $message = "Product and initial stock added successfully!";

        } catch (Exception $e) {

            $conn->rollback();

            if (isset($stmt) && $stmt instanceof mysqli_stmt) {
                $stmt->close();
            }

            if (isset($batch) && $batch instanceof mysqli_stmt) {
                $batch->close();
            }

            $error = "Could not add product. Check the Product ID and try again.";
        }
    }
}

// Fetch all products
$sql = "SELECT * FROM products ORDER BY product_id ASC";

$result = $conn->query($sql);

if (!$result) {
    die("Products query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | Save Stock</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="dashboard">

    <aside class="sidebar">

        <div class="logo">
            🌱 Save<span>Stock</span>
        </div>

        <p class="nav-title">MAIN MENU</p>

        <a href="../index.php" class="nav-link">
            📊 Dashboard
        </a>

        <a href="inventory.php" class="nav-link">
            📦 Inventory
        </a>

        <a href="products.php" class="nav-link active">
            🛒 Products
        </a>

        <a href="#" class="nav-link">
            🚚 Suppliers
        </a>

       <p class="nav-title">SMART MANAGEMENT</p>

<a href="smart_recommendations.php" class="nav-link">
    💡 Smart Recommendations

        <a href="#" class="nav-link">🗑️ Waste Management</a>
        <a href="#" class="nav-link">🧾 Orders</a>
        <a href="#" class="nav-link">📈 Reports</a>

        <p class="nav-title">SYSTEM</p>
        <a href="#" class="nav-link">⚙️ Settings</a>

    </aside>

    <main class="main-content">

        <h1>Product Management</h1>
        <p>Manage your supermarket products and product information.</p>

        
<div class="panel table-panel">

    <h2>All Products</h2>
<?php if ($message !== ""): ?>
    <p style="color: green;">
        <?= htmlspecialchars($message) ?>
    </p>
<?php endif; ?>

<?php if ($error !== ""): ?>
    <p style="color: red;">
        <?= htmlspecialchars($error) ?>
    </p>
<?php endif; ?>
    <button type="button"
        onclick="document.getElementById('addProductForm').style.display='block'">
        + Add Product
    </button>

    <div id="addProductForm" style="display:none; margin:20px 0;">

        <h3>Add New Product</h3>

        <form method="POST" action="">

            <label>Product ID</label>
            <input type="text" name="product_id" required>

            <br><br>

            <label>Product Name</label>
            <input type="text" name="product_name" required>

            <br><br>

            <label>Category</label>
            <select name="category" required>
                <option value="">Select Category</option>
                <option value="Dairy">Dairy</option>
                <option value="Bakery">Bakery</option>
                <option value="Fruits">Fruits</option>
                <option value="Vegetables">Vegetables</option>
                <option value="Meat">Meat</option>
                <option value="Beverages">Beverages</option>
                <option value="Other">Other</option>
            </select>

            <br><br>

            <label>Selling Price (৳)</label>
            <input type="number" name="unit_price" step="0.01" min="0" required>

            <br><br>

            <label>Cost Price (৳)</label>
            <input type="number" name="cost_price" step="0.01" min="0" required>

            <br><br>

            <label>Stock Quantity</label>
            <input type="number" name="stock_quantity" min="0" required>

            <br><br>

            <label>Expiry Date</label>
            <input type="date" name="expiry_date" required>

            <br><br>

            <button type="submit" name="add_product">
                Save Product
            </button>

            <button type="button"
                onclick="document.getElementById('addProductForm').style.display='none'">
                Cancel
            </button>

        </form>

    </div>

            <table>
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Selling Price</th>
                        <th>Cost Price</th>
                        <th>Stock Quantity</th>
                        <th>Expiry Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($product = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['product_id']) ?></td>
                            <td><?= htmlspecialchars($product['product_name']) ?></td>
                            <td><?= htmlspecialchars($product['category']) ?></td>
                            <td>৳<?= number_format($product['unit_price'], 2) ?></td>
                            <td>৳<?= number_format($product['cost_price'], 2) ?></td>
                            <td><?= (int)$product['stock_quantity'] ?></td>
                            <td><?= date('d M Y', strtotime($product['expiry_date'])) ?></td>
                            <td>
    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');">
        <input type="hidden" name="product_id"
               value="<?= htmlspecialchars($product['product_id']) ?>">

        <button type="submit" name="delete_product">
            Delete
        </button>
    </form>
</td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

        </div>

    </main>

</div>

</body>
</html>
