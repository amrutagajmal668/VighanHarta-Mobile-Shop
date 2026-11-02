<?php
require_once "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $category = $_POST["category"] ?? "";
    $price = $_POST["price"] ?? "";
    $stock = $_POST["stock"] ?? "0";
    $description = trim($_POST["description"] ?? "");

    $allowedCategories = ["accessory", "used-mobile", "new-mobile"];

    if (
        $name !== "" &&
        in_array($category, $allowedCategories, true) &&
        is_numeric($price) && (float)$price >= 0 &&
        filter_var($stock, FILTER_VALIDATE_INT) !== false &&
        (int)$stock >= 0
    ) {
        $stmt = $conn->prepare(
            "INSERT INTO products (name, category, price, stock, description)
             VALUES (?, ?, ?, ?, ?)"
        );

        $priceValue = (float)$price;
        $stockValue = (int)$stock;

        $stmt->bind_param(
            "ssdis",
            $name,
            $category,
            $priceValue,
            $stockValue,
            $description
        );

        if ($stmt->execute()) {
            $message = "Product added successfully!";
        } else {
            $message = "Could not add product.";
        }

        $stmt->close();
    } else {
        $message = "Please enter valid product details.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | VighanHarta</title>
</head>
<body>
    <h1>VighanHarta - Add Product</h1>

    <p><?php echo htmlspecialchars($message, ENT_QUOTES, "UTF-8"); ?></p>

    <form method="POST">
        <label>Product Name:</label><br>
        <input type="text" name="name" required><br><br>

        <label>Category:</label><br>
        <select name="category" required>
            <option value="accessory">Accessories</option>
            <option value="used-mobile">Used Mobiles</option>
            <option value="new-mobile">New Mobiles</option>
        </select><br><br>

        <label>Price (₹):</label><br>
        <input type="number" name="price" min="0" step="0.01" required><br><br>

        <label>Stock Quantity:</label><br>
        <input type="number" name="stock" min="0" value="1" required><br><br>

        <label>Description:</label><br>
        <textarea name="description"></textarea><br><br>

        <button type="submit">Add Product</button>
    </form>
</body>
</html>