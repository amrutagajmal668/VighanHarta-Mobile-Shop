
<?php
session_start();

require_once "db.php";

header("Content-Type: application/json; charset=UTF-8");

$method = $_SERVER["REQUEST_METHOD"];

function sendJson($status, $data) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Only logged-in admins can add, edit, or delete products.
if (
    in_array($method, ["POST", "DELETE"], true) &&
    empty($_SESSION["admin_logged_in"])
) {
    sendJson(401, ["error" => "Please log in as admin"]);
}

if ($method === "GET") {
    $result = $conn->query(
        "SELECT id, name, category, price, stock, image, description
         FROM products ORDER BY id DESC"
    );

    if (!$result) {
        sendJson(500, ["error" => "Could not load products"]);
    }

    $products = [];

    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    echo json_encode($products);
    exit;
}

if ($method === "POST") {

    // Read product details from FormData.
    $name = trim($_POST["name"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $price = $_POST["price"] ?? null;
    $stock = $_POST["stock"] ?? null;
    $description = trim($_POST["description"] ?? "");
    $id = (int)($_POST["id"] ?? 0);

    if (
        $name === "" ||
        !in_array($category, [
            "accessory",
            "used-mobile",
            "new-mobile"
        ], true) ||
        !is_numeric($price) ||
        (float)$price <= 0 ||
        !is_numeric($stock) ||
        (int)$stock < 0 ||
        (float)$stock != (int)$stock
    ) {
        sendJson(400, ["error" => "Invalid product details"]);
    }

    $price = (float)$price;
    $stock = (int)$stock;

    // Handle uploaded image.
    $image = "";

    if (isset($_FILES["image"]) &&
        $_FILES["image"]["error"] === UPLOAD_ERR_OK) {

        $file = $_FILES["image"];

        if ($file["size"] > 1024 * 1024) {
            sendJson(400, ["error" => "Image must be under 1 MB"]);
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file["tmp_name"]);

        $allowed = [
            "image/jpeg" => "jpg",
            "image/png"  => "png",
            "image/webp" => "webp",
            "image/gif"  => "gif"
        ];

        if (!isset($allowed[$mime])) {
            sendJson(400, ["error" => "Please upload a valid image"]);
        }

        $uploadDir = __DIR__ . "/uploads/";

        if (!is_dir($uploadDir) &&
            !mkdir($uploadDir, 0755, true)) {
            sendJson(500, ["error" => "Could not create uploads folder"]);
        }

        $fileName = bin2hex(random_bytes(12)) . "." . $allowed[$mime];
        $destination = $uploadDir . $fileName;

        if (!move_uploaded_file($file["tmp_name"], $destination)) {
            sendJson(500, ["error" => "Image upload failed"]);
        }

        $image = "uploads/" . $fileName;

    } elseif ($id > 0) {
        // When editing a product, keep its existing image if no new image is sent.
        $stmt = $conn->prepare("SELECT image FROM products WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($existingImage);

        if ($stmt->fetch()) {
            $image = $existingImage;
        }

        $stmt->close();
    } else {
        sendJson(400, ["error" => "Please select a product image"]);
    }

    if ($id > 0) {
        $stmt = $conn->prepare(
            "UPDATE products
             SET name=?, category=?, price=?, stock=?, image=?, description=?
             WHERE id=?"
        );

        $stmt->bind_param(
            "ssdissi",
            $name, $category, $price, $stock, $image, $description, $id
        );
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO products
             (name, category, price, stock, image, description)
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssdiss",
            $name, $category, $price, $stock, $image, $description
        );
    }

    if (!$stmt->execute()) {
        $stmt->close();
        sendJson(500, ["error" => "Could not save product to database"]);
    }

    $productId = $id > 0 ? $id : $stmt->insert_id;
    $stmt->close();

    sendJson(200, [
        "success" => true,
        "id" => $productId,
        "message" => "Product saved successfully!"
    ]);
}

if ($method === "DELETE") {
    $id = (int)($_GET["id"] ?? 0);

    if ($id <= 0) {
        sendJson(400, ["error" => "Invalid product ID"]);
    }

    $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $deleted = $stmt->affected_rows > 0;
    $stmt->close();

    echo json_encode(["success" => $deleted]);
    exit;
}

sendJson(405, ["error" => "Method not allowed"]);
?>