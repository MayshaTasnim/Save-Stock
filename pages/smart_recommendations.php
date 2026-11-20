
<?php
require_once "../config/db.php";

// Get all available inventory batches
$sql = "
    SELECT
        b.batch_id,
        p.product_id,
        p.product_name,
        p.category,
        p.unit_price,
        b.quantity_remaining,
        b.expiry_date,
        DATEDIFF(b.expiry_date, CURDATE()) AS days_left
    FROM inventory_batches b
    INNER JOIN products p
        ON b.product_id = p.product_id
    WHERE b.quantity_remaining > 0
    ORDER BY b.expiry_date ASC
";

$result = $conn->query($sql);

if (!$result) {
    die("Recommendation query failed: " . $conn->error);
}

// Generate recommendations

function getRecommendation($days) {

    if ($days < 0) {
        return [
            "Expired",
            "danger",
            "Remove from sale and record as waste.",
            "Expired",
            0
        ];
    }

    if ($days == 0) {
        return [
            "Expires Today",
            "danger",
            "Urgent clearance, only if still safe and within its labelled date.",
            "Critical",
            15
        ];
    }

    if ($days == 1) {
        return [
            "Critical",
            "danger",
            "Suggest a discount and priority display.",
            "High",
            15
        ];
    }

    if ($days <= 3) {
        return [
            "High Risk",
            "warning",
            "Create a promotional bundle or offer a special discount.",
            "High",
            10
        ];
    }

    if ($days <= 7) {
        return [
            "Upcoming",
            "warning",
            "Move stock to the front using FEFO and promote it.",
            "Medium",
            5
        ];
    }

    return [
        "Safe",
        "good",
        "Continue normal sales and monitor expiry dates.",
        "Low",
        0
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Recommendations - Save Stock</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="dashboard">

    <aside class="sidebar">
        <div class="logo">🌱 Save<span>Stock</span></div>

        <p class="nav-title">MAIN MENU</p>

        <a href="../index.php" class="nav-link">📊 Dashboard</a>
        <a href="inventory.php" class="nav-link">📦 Inventory</a>
        <a href="products.php" class="nav-link">🛒 Products</a>
        <a href="#" class="nav-link">🚚 Suppliers</a>

        <p class="nav-title">SMART MANAGEMENT</p>

        <a href="smart_recommendations.php" class="nav-link active">
            💡 Smart Recommendations
        </a>

        <a href="#" class="nav-link">🗑️ Waste Management</a>
        <a href="#" class="nav-link">🧾 Orders</a>
        <a href="#" class="nav-link">📈 Reports</a>

        <p class="nav-title">SYSTEM</p>
        <a href="#" class="nav-link">⚙️ Settings</a>
    </aside>

    <main class="main-content">

        <div class="topbar">
            <div>
                <h1>Smart Recommendations 💡</h1>
                <p>Prevent food waste before products expire.</p>
            </div>
        </div>

        <section class="panel">
            <h2>Expiry-Based Recommendations</h2>
            <p>
                The system analyzes inventory batches and suggests
                actions based on their remaining shelf life.
            </p>

            <div style="overflow-x:auto;">

                <table>
                    <thead>
                        <tr>
<th>Product</th>
<th>Quantity</th>
<th>Expiry Date</th>
<th>Days Left</th>
<th>Status</th>
<th>Priority</th>
<th>Discount</th>
<th>Suggested Price</th>
<th>Recommended Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($result->num_rows > 0): ?>

                        <?php while ($item = $result->fetch_assoc()): ?>

                            <?php
                            $recommendation =
                                getRecommendation((int)$item['days_left']);
                            ?>
                        
<tr>
    <td>
        <?= htmlspecialchars($item['product_name']) ?>
    </td>

    <td>
        <?= (int)$item['quantity_remaining'] ?>
    </td>

    <td>
        <?= htmlspecialchars($item['expiry_date']) ?>
    </td>

    <td>
        <?= (int)$item['days_left'] ?>
    </td>

    <td>
        <span class="status <?= $recommendation[1] ?>">
            <?= htmlspecialchars($recommendation[0]) ?>
        </span>
    </td>

    <td>
        <strong>
            <?= htmlspecialchars($recommendation[3]) ?>
        </strong>
    </td>

    <td>
        <?= (int)$recommendation[4] ?>%
    </td>

    <td>
        ৳<?= number_format(
            (float)$item['unit_price'] *
            (1 - $recommendation[4] / 100),
            2
        ) ?>
    </td>

    <td>
        <?= htmlspecialchars($recommendation[2]) ?>
    </td>
</tr>


                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="9">
                                No available inventory batches found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>

            </div>
        </section>

    </main>

</div>

</body>
</html>
