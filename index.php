<?php
require_once "config/db.php";

// Calculate dashboard statistics from MySQL

$totalProductsResult = $conn->query(
    "SELECT COUNT(*) AS total FROM products"
);

$totalStockResult = $conn->query(
    "SELECT COALESCE(SUM(quantity_remaining), 0) AS total
     FROM inventory_batches"
);

$expiringResult = $conn->query(
    "SELECT COUNT(*) AS total
     FROM inventory_batches
     WHERE quantity_remaining > 0
     AND expiry_date >= CURDATE()
     AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)"
);

$totalProducts = $totalProductsResult
    ? (int)$totalProductsResult->fetch_assoc()['total']
    : 0;

$totalStock = $totalStockResult
    ? (int)$totalStockResult->fetch_assoc()['total']
    : 0;

$expiringSoon = $expiringResult
    ? (int)$expiringResult->fetch_assoc()['total']
    : 0;
// Dashboard statistics

$stats = [
    [
        "title" => "Total Products",
        "value" => number_format($totalProducts),
        "note" => "Products in database",
        "icon" => "📦"
    ],
    [
        "title" => "Available Stock",
        "value" => number_format($totalStock),
        "note" => "Total remaining quantity",
        "icon" => "🟢"
    ],
    [
        "title" => "Expiring Soon",
        "value" => number_format($expiringSoon),
        "note" => "Batches expiring within 3 days",
        "icon" => "⏳"
    ],
    [
        "title" => "Food Waste",
        "value" => "Not tracked",
        "note" => "Awaiting waste records",
        "icon" => "♻️"
    ]
];
// Fetch real inventory batches from MySQL

$sql = "
    SELECT
        p.product_name,
        b.quantity_remaining,
        b.expiry_date,
        b.batch_id,
        DATEDIFF(b.expiry_date, CURDATE()) AS days_left
    FROM products p
    INNER JOIN inventory_batches b
        ON p.product_id = b.product_id
    WHERE b.quantity_remaining > 0
    ORDER BY b.expiry_date ASC
";

$result = $conn->query($sql);

$products = [];

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $days = (int)$row['days_left'];

        if ($days < 0) {
            $status = "danger";
            $status_text = "Expired";
        } elseif ($days <= 3) {
            $status = "warning";
            $status_text = "Expiring Soon";
        } else {
            $status = "good";
            $status_text = "Normal";
        }

        $products[] = [
            "name" => $row['product_name'],
            "quantity" => $row['quantity_remaining'],
            "expiry" => $row['expiry_date'],
            "status" => $status,
            "status_text" => $status_text
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Save Stock | Dashboard</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="dashboard">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="logo">
            🌱 Save<span>Stock</span>
        </div>

        <p class="nav-title">MAIN MENU</p>

        <a href="index.php" class="nav-link active">
            📊 Dashboard
        </a>

        <a href="pages/inventory.php" class="nav-link">
    📦 Inventory
</a>
<a href="http://localhost/save_stock/pages/products.php" class="nav-link">
    🛒 Products
</a>
        <a href="#" class="nav-link">
            🚚 Suppliers
        </a>

        <p class="nav-title">SMART MANAGEMENT</p>

<a href="pages/smart_recommendations.php" class="nav-link">
    💡 Smart Recommendations
</a>
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


    <!-- MAIN CONTENT -->

    <main class="main-content">

        <!-- TOP BAR -->

        <header class="topbar">

            <div>
                <h2>Dashboard Overview</h2>
                <p>Welcome back! Here's your supermarket stock overview.</p>
            </div>

            <div class="profile">
                👤 Admin
            </div>

        </header>


        <!-- STATISTICS -->

        <section class="stats-grid">

            <?php foreach ($stats as $stat): ?>

                <div class="stat-card">

                    <p class="stat-label">
                        <?= htmlspecialchars($stat["icon"]) ?>
                        <?= htmlspecialchars($stat["title"]) ?>
                    </p>

                    <h2 class="stat-value">
                        <?= htmlspecialchars($stat["value"]) ?>
                    </h2>

                    <p class="stat-note">
                        <?= htmlspecialchars($stat["note"]) ?>
                    </p>

                </div>

            <?php endforeach; ?>

        </section>


        <!-- WASTE REDUCTION AND INVENTORY -->

        <section class="content-grid">

            <div class="panel">

                <h3>🌱 Food Waste Reduction Goal</h3>

                <p class="panel-description">
                    Track progress toward our 30% waste reduction target.
                </p>

                <div class="progress-item">

                    <div class="progress-info">
                        <span>Waste Reduction Target</span>
                        <strong>30%</strong>
                    </div>

                    <div class="progress-bar">
                        <div class="progress-fill"
                             style="width: 30%;">
                        </div>
                    </div>

                </div>

                <p class="panel-description">
                    This is a target, not a measured achievement.
                    Actual reduction will be calculated using
                    historical and current waste records.
                </p>

                <div class="progress-item">

                    <div class="progress-info">
                        <span>Inventory Monitoring</span>
                        <strong>Active</strong>
                    </div>

                    <div class="progress-bar">
                        <div class="progress-fill"
                             style="width: 100%;">
                        </div>
                    </div>

                </div>

            </div>


            <div class="panel">

                <h3>📦 Inventory Status</h3>

                <p class="panel-description">
                    Sample stock distribution
                </p>

                <div class="progress-item">

                    <div class="progress-info">
                        <span>Available Stock</span>
                        <strong>86%</strong>
                    </div>

                    <div class="progress-bar">
                        <div class="progress-fill"
                             style="width: 86%;">
                        </div>
                    </div>

                </div>

                <div class="progress-item">

                    <div class="progress-info">
                        <span>Low Stock</span>
                        <strong>10%</strong>
                    </div>

                    <div class="progress-bar">
                        <div class="progress-fill"
                             style="width: 10%; background: #e6a23c;">
                        </div>
                    </div>

                </div>

                <div class="progress-item">

                    <div class="progress-info">
                        <span>Critical Stock</span>
                        <strong>4%</strong>
                    </div>

                    <div class="progress-bar">
                        <div class="progress-fill"
                             style="width: 4%; background: #e45b5b;">
                        </div>
                    </div>

                </div>

            </div>

        </section>


        <!-- EXPIRING PRODUCTS -->

        <section class="table-panel">

            <h3>⏰ Expiring Soon</h3>

            <p class="panel-description">
                Sample products requiring attention.
            </p>

            <table>

                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
            <tbody>

    <?php if (count($products) > 0): ?>

        <?php foreach ($products as $product): ?>

            <tr>
                <td>
                    <?= htmlspecialchars($product['name']) ?>
                </td>

                <td>
                    <?= htmlspecialchars($product['quantity']) ?>
                </td>

                <td>
                    <?= htmlspecialchars(date(
                        'd M Y',
                        strtotime($product['expiry'])
                    )) ?>
                </td>

                <td>
                    <span class="status <?= htmlspecialchars($product['status']) ?>">
                        <?= htmlspecialchars($product['status_text']) ?>
                    </span>
                </td>
            </tr>

        <?php endforeach; ?>

    <?php else: ?>

        <tr>
            <td colspan="4">No active inventory batches found.</td>
        </tr>

    <?php endif; ?>

</tbody>

            </table>

        </section>


        <p style="text-align:center; margin-top:30px;
                  color:#829087; font-size:12px;">

            © 2026 Save Stock | Reducing Food Waste, Building a Better Future 🌱

        </p>

    </main>

</div>

</body>
</html>
