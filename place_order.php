 <?php
header("Content-Type: application/json");

require_once "db.php";

function sendResponse($code, $data) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendResponse(405, [
        "success" => false,
        "message" => "Invalid request method."
    ]);
}

$data = json_decode(file_get_contents("php://input"), true);

if (!is_array($data)) {
    sendResponse(400, [
        "success" => false,
        "message" => "Invalid order data."
    ]);
}

$name = trim($data["customerName"] ?? "");
$mobile = trim($data["phone"] ?? "");
$note = trim($data["pickupNote"] ?? "");
$items = $data["items"] ?? [];

if (
    $name === "" ||
    !preg_match('/^[6-9][0-9]{9}$/', $mobile) ||
    !is_array($items) ||
    count($items) === 0
) {
    sendResponse(400, [
        "success" => false,
        "message" => "Enter valid customer details and cart items."
    ]);
}

$validatedItems = [];
$total = 0;

foreach ($items as $item) {
    $productId = filter_var(
        $item["productId"] ?? null,
        FILTER_VALIDATE_INT
    );

    $quantity = filter_var(
        $item["quantity"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!$productId || !$quantity || $quantity < 1) {
        sendResponse(400, [
            "success" => false,
            "message" => "Invalid product or quantity."
        ]);
    }

    $stmt = $conn->prepare(
        "SELECT id, name, price, stock
         FROM products
         WHERE id = ?"
    );

    if (!$stmt) {
        sendResponse(500, [
            "success" => false,
            "message" => "Could not check product details."
        ]);
    }

    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    if (!$product) {
        sendResponse(400, [
            "success" => false,
            "message" => "A selected product was not found."
        ]);
    }

    if ($quantity > (int)$product["stock"]) {
        sendResponse(400, [
            "success" => false,
            "message" => "Insufficient stock for " . $product["name"]
        ]);
    }

    $price = (float)$product["price"];
    $subtotal = $price * $quantity;
    $total += $subtotal;

    $validatedItems[] = [
        "product_id" => (int)$product["id"],
        "product_name" => $product["name"],
        "quantity" => $quantity,
        "price" => $price
    ];
}

if ($total <= 0) {
    sendResponse(400, [
        "success" => false,
        "message" => "Invalid order total."
    ]);
}

$status = "Pending";

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare(
        "INSERT INTO orders
         (customer_name, mobile, pickup_note, total_amount, status)
         VALUES (?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        throw new Exception("Could not prepare order.");
    }

    $stmt->bind_param(
        "sssds",
        $name,
        $mobile,
        $note,
        $total,
        $status
    );

    if (!$stmt->execute()) {
        throw new Exception("Could not save order.");
    }

    $orderId = $conn->insert_id;
    $stmt->close();

    $itemStmt = $conn->prepare(
        "INSERT INTO order_items
         (order_id, product_id, product_name, quantity, price)
         VALUES (?, ?, ?, ?, ?)"
    );

    if (!$itemStmt) {
        throw new Exception("Could not prepare order items.");
    }

    foreach ($validatedItems as $item) {
        $productId = $item["product_id"];
        $productName = $item["product_name"];
        $quantity = $item["quantity"];
        $price = $item["price"];

        $itemStmt->bind_param(
            "iisid",
            $orderId,
            $productId,
            $productName,
            $quantity,
            $price
        );

        if (!$itemStmt->execute()) {
            throw new Exception("Could not save product details.");
        }
    }

    $itemStmt->close();
    $conn->commit();

    sendResponse(200, [
        "success" => true,
        "message" => "Order and product details saved successfully.",
        "orderId" => $orderId,
        "totalAmount" => $total
    ]);

} catch (Throwable $e) {
    $conn->rollback();

    error_log("Order save error: " . $e->getMessage());

    sendResponse(500, [
        "success" => false,
        "message" => "Could not save order. Please try again."
    ]);
}

$conn->close();
?>