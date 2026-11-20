<?php
require_once "../config/db.php";

// Get all products and their current batch stock
$sql = "
    SELECT
        p.product_id,
        p.product_name,
        p.category,
        p.unit_price,
        COALESCE(SUM(b.quantity_remaining), 0) AS available_stock,
        MIN(
            CASE
                WHEN b.quantity_remaining > 0 THEN b.expiry_date
                ELSE NULL
            END
        ) AS nearest_expiry
    FROM products p
    LEFT JOIN inventory_batches b
        ON p.product_id = b.product_id
    GROUP BY
        p.product_id,
        p.product_name,
        p.category,
        p.unit_price
    ORDER BY p.product_name ASC
";

$result = $conn->query($sql);

if (!$result) {
    die("Inventory query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory | Save Stock</title>

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

<div class="dashboard">

    <!-- SIDEBAR -->
    <aside class="sidebar">

        <div class="logo">
            🌱 Save<span>Stock</span>
        </div>

        <p class="nav-title">MAIN MENU</p>

        <a href="../index.php" class="nav-link">
            📊 Dashboard
        </a>

        <a href="inventory.php" class="nav-link active">
            📦 Inventory
        </a>

        <a href="products.php" class="nav-link">
            🛒 Products
        </a>

        <a href="#" class="nav-link">
            🚚 Suppliers
        </a>

        <p class="nav-title">SMART MANAGEMENT</p>

<a href="smart_recommendations.php" class="nav-link">
    💡 Smart Recommendations

        <a href="#" class="nav-link">
            🗑️ Waste Management
        </a>

        <a href="#" class="nav-link">
            🧾 Orders
        </a>

        <a href="#" class="nav-link">
            📈 Reports
        </a>

        <p class="nav-title">SYSTEM</p>

        <a href="#" class="nav-link">
            ⚙️ Settings
        </a>

    </aside>

<div class="main-content">

    <h1>Inventory Management</h1>
    <p>Monitor your products, stock levels and expiry dates.</p>

    <div class="panel table-panel">

        <h2>All Products</h2>

        <table>
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Available Stock</th>
                    <th>Nearest Expiry</th>
                </tr>
            </thead>

            <tbody>

                <?php while ($product = $result->fetch_assoc()): ?>

                    <tr>
                        <td><?= htmlspecialchars($product['product_id']) ?></td>

                        <td><?= htmlspecialchars($product['product_name']) ?></td>

                        <td><?= htmlspecialchars($product['category']) ?></td>

                        <td>৳<?= number_format($product['unit_price'], 2) ?></td>

                        <td><?= (int)$product['available_stock'] ?></td>

                        <td>
                            <?= $product['nearest_expiry']
                                ? date('d M Y', strtotime($product['nearest_expiry']))
                                : 'No active stock' ?>
                        </td>
                    </tr>

                <?php endwhile; ?>

            </tbody>
        </table>

    </div>

</div>

</body>
</html>
