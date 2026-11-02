   <?php
session_start();

if (empty($_SESSION["admin_logged_in"])) {
    header("Location: admin_login.php");
    exit;
}

require_once "db.php";

$message = "";
$error = "";

// Update order status and manage stock
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_status"])) {

    $orderId = filter_var(
        $_POST["order_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    $newStatus = $_POST["status"] ?? "";

    $allowedStatuses = [
        "Pending",
        "Confirmed",
        "Completed",
        "Cancelled"
    ];

    if (!$orderId || !in_array($newStatus, $allowedStatuses, true)) {
        $error = "Invalid order ID or status.";
    } else {
        try {
            $conn->begin_transaction();

            // Lock the order while updating it
            $stmt = $conn->prepare(
                "SELECT status, stock_deducted
                 FROM orders
                 WHERE id = ?
                 FOR UPDATE"
            );
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$order) {
                throw new Exception("Order not found.");
            }

            $oldStatus = $order["status"];
            $stockDeducted = (int)$order["stock_deducted"];

            // Once confirmed, do not move back to Pending
            if ($oldStatus === "Confirmed" && $newStatus === "Pending") {
                throw new Exception(
                    "A confirmed order cannot be changed back to Pending."
                );
            }

            // Deduct stock when first confirming the order
            if (
                $newStatus === "Confirmed" &&
                $oldStatus !== "Confirmed" &&
                $stockDeducted === 0
            ) {
                $itemsStmt = $conn->prepare(
                    "SELECT product_id, quantity
                     FROM order_items
                     WHERE order_id = ?
                     FOR UPDATE"
                );
                $itemsStmt->bind_param("i", $orderId);
                $itemsStmt->execute();
                $itemsResult = $itemsStmt->get_result();

                $items = [];
                while ($item = $itemsResult->fetch_assoc()) {
                    $items[] = $item;
                }
                $itemsStmt->close();

                if (count($items) === 0) {
                    throw new Exception("No products found for this order.");
                }

                foreach ($items as $item) {
                    $productId = (int)$item["product_id"];
                    $quantity = (int)$item["quantity"];

                    $stockStmt = $conn->prepare(
                        "UPDATE products
                         SET stock = stock - ?
                         WHERE id = ? AND stock >= ?"
                    );
                    $stockStmt->bind_param(
                        "iii",
                        $quantity,
                        $productId,
                        $quantity
                    );
                    $stockStmt->execute();

                    if ($stockStmt->affected_rows !== 1) {
                        $stockStmt->close();
                        throw new Exception(
                            "Insufficient stock for one of the products."
                        );
                    }

                    $stockStmt->close();
                }

                $stockDeducted = 1;
            }

            // Restore stock if a confirmed order is cancelled
            if (
                $newStatus === "Cancelled" &&
                $oldStatus === "Confirmed" &&
                $stockDeducted === 1
            ) {
                $itemsStmt = $conn->prepare(
                    "SELECT product_id, quantity
                     FROM order_items
                     WHERE order_id = ?
                     FOR UPDATE"
                );
                $itemsStmt->bind_param("i", $orderId);
                $itemsStmt->execute();
                $itemsResult = $itemsStmt->get_result();

                while ($item = $itemsResult->fetch_assoc()) {
                    $productId = (int)$item["product_id"];
                    $quantity = (int)$item["quantity"];

                    $restoreStmt = $conn->prepare(
                        "UPDATE products
                         SET stock = stock + ?
                         WHERE id = ?"
                    );
                    $restoreStmt->bind_param(
                        "ii",
                        $quantity,
                        $productId
                    );
                    $restoreStmt->execute();

                    if ($restoreStmt->affected_rows !== 1) {
                        $restoreStmt->close();
                        throw new Exception(
                            "Could not restore product stock."
                        );
                    }

                    $restoreStmt->close();
                }

                $stockDeducted = 0;
            }

            // Save the updated status and stock tracking flag
            $updateStmt = $conn->prepare(
                "UPDATE orders
                 SET status = ?, stock_deducted = ?
                 WHERE id = ?"
            );
            $updateStmt->bind_param(
                "sii",
                $newStatus,
                $stockDeducted,
                $orderId
            );

            if (!$updateStmt->execute()) {
                throw new Exception("Could not update order status.");
            }

            $updateStmt->close();
            $conn->commit();

            $message = "Order status updated successfully!";

        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}
// Get products for a particular order
function getOrderItems($conn, $orderId)
{
    $items = [];

    $stmt = $conn->prepare(
        "SELECT product_name, quantity, price
         FROM order_items
         WHERE order_id = ?"
    );

    if (!$stmt) {
        return $items;
    }

    $stmt->bind_param("i", $orderId);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    $stmt->close();

    return $items;
}

// Fetch all orders
$orders = $conn->query(
    "SELECT id, customer_name, mobile, pickup_note,
            total_amount, status, created_at
     FROM orders
     ORDER BY id DESC"
);

if (!$orders) {
    $error = "Unable to load orders. Please check your database tables.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Orders - VighanHarta</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #102541;
        }

        .header {
            background: #102541;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0 0 8px;
        }

        .header p {
            margin: 0;
        }

        .top-links {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .top-links a {
            display: inline-block;
            background: #ff8c00;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
        }

        .message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .order-card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            border-left: 5px solid #ff8c00;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .order-card h2 {
            margin-top: 0;
        }

        .details p {
            margin: 8px 0;
            overflow-wrap: anywhere;
        }

        .table-wrapper {
            overflow-x: auto;
            margin-top: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 400px;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #102541;
            color: white;
        }

        .status-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        select, button {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
        }

        button {
            background: #ff8c00;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #e67e00;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            background: #eee;
            border-radius: 5px;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            padding: 30px;
            background: white;
            border-radius: 10px;
        }

        @media (max-width: 600px) {
            body {
                padding: 10px;
            }

            .order-card {
                padding: 12px;
            }
        }
    </style>
</head>

<body>

<div class="header">
    <h1>VighanHarta - Manage Orders</h1>
    <p>View customer orders and manage their status.</p>

    <div class="top-links">
        <a href="admin_test.php">Manage Products</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<?php if ($message !== ""): ?>
    <div class="message">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php if ($error !== ""): ?>
    <div class="error">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if ($orders && $orders->num_rows > 0): ?>

    <?php while ($order = $orders->fetch_assoc()): ?>

        <div class="order-card">

            <h2>
                Order #<?= (int)$order["id"] ?>
            </h2>

            <div class="details">

                <p>
                    <strong>Customer Name:</strong>
                    <?= htmlspecialchars($order["customer_name"] ?? "") ?>
                </p>

                <p>
                    <strong>Mobile:</strong>
                    <?= htmlspecialchars($order["mobile"] ?? "") ?>
                </p>

                <p>
                    <strong>Pickup Note:</strong>
                    <?= htmlspecialchars($order["pickup_note"] ?? "Not provided") ?>
                </p>

                <p>
                    <strong>Total Amount:</strong>
                    ₹<?= number_format((float)$order["total_amount"], 2) ?>
                </p>

                <p>
                    <strong>Order Date:</strong>
                    <?= htmlspecialchars($order["created_at"] ?? "") ?>
                </p>

                <p>
                    <strong>Current Status:</strong>
                    <span class="status">
                        <?= htmlspecialchars($order["status"] ?? "Pending") ?>
                    </span>
                </p>

            </div>

            <h3>Products Ordered</h3>

            <?php $items = getOrderItems($conn, (int)$order["id"]); ?>

            <?php if (count($items) > 0): ?>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($items as $item): ?>

                                <tr>
                                    <td>
                                        <?= htmlspecialchars($item["product_name"]) ?>
                                    </td>

                                    <td>
                                        <?= (int)$item["quantity"] ?>
                                    </td>

                                    <td>
                                        ₹<?= number_format((float)$item["price"], 2) ?>
                                    </td>

                                    <td>
                                        ₹<?= number_format(
                                            (float)$item["price"] * (int)$item["quantity"],
                                            2
                                        ) ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>

                <p>No product details found for this order.</p>

            <?php endif; ?>

            <form method="POST" action="admin_orders.php" class="status-form">

                <input
                    type="hidden"
                    name="order_id"
                    value="<?= (int)$order["id"] ?>"
                >

                <select name="status" required>
                    <?php
                    $statuses = [
                        "Pending",
                        "Confirmed",
                        "Completed",
                        "Cancelled"
                    ];

                    foreach ($statuses as $statusOption):
                    ?>
                        <option
                            value="<?= htmlspecialchars($statusOption) ?>"
                            <?= (($order["status"] ?? "Pending") === $statusOption)
                                ? "selected"
                                : "" ?>
                        >
                            <?= htmlspecialchars($statusOption) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" name="update_status">
                    Update Status
                </button>

            </form>

        </div>

    <?php endwhile; ?>

<?php elseif ($orders): ?>

    <div class="empty">
        <h2>No Orders Found</h2>
        <p>Customer orders will appear here after checkout.</p>
    </div>

<?php endif; ?>

</body>
</html>

<?php
$conn->close();
?>