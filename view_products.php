<?php
require_once "db.php";

$sql = "SELECT id, name, category, price, stock, description
        FROM products ORDER BY id DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VighanHarta Products</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f5f8;
            margin: 0;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #172554;
        }

        .products {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .product {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px #ddd;
        }

        .price {
            color: #ea580c;
            font-weight: bold;
        }
    </style>
</head>

<body>

<h1>VighanHarta Products</h1>

<div class="products">

<?php if ($result && $result->num_rows > 0): ?>

    <?php while ($product = $result->fetch_assoc()): ?>

        <div class="product">
            <h2>
                <?= htmlspecialchars($product["name"], ENT_QUOTES, "UTF-8") ?>
            </h2>

            <p>
                Category:
                <?= htmlspecialchars($product["category"], ENT_QUOTES, "UTF-8") ?>
            </p>

            <p class="price">
                ₹<?= number_format((float)$product["price"], 2) ?>
            </p>

            <p>
                Stock: <?= (int)$product["stock"] ?>
            </p>

            <p>
                <?= htmlspecialchars($product["description"] ?? "", ENT_QUOTES, "UTF-8") ?>
            </p>
        </div>

    <?php endwhile; ?>

<?php else: ?>

    <p>No products available.</p>

<?php endif; ?>

</div>

</body>
</html>