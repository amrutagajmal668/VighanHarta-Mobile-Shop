<?php
  
session_start();
require_once "db.php";

// Admin login check
if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {
    header("Location: admin_login.php");
    exit;
}

$message = "";

// Accept or Reject Sell Request
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["request_id"], $_POST["action"])
) {
    $request_id = (int) $_POST["request_id"];
    $action = $_POST["action"];

    if (
        $request_id > 0 &&
        in_array($action, ["Accepted", "Rejected"], true)
    ) {
        $stmt = $conn->prepare(
            "UPDATE sell_requests SET status = ? WHERE id = ?"
        );

        if ($stmt) {
            $stmt->bind_param("si", $action, $request_id);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: admin_sell_requests.php?updated=1");
                exit;
            } else {
                $message = "Status update failed. Please try again.";
                $stmt->close();
            }
        } else {
            $message = "Unable to prepare status update.";
        }
    }
}

// Fetch Sell Requests
$sql = "SELECT * FROM sell_requests ORDER BY created_at DESC";
$result = $conn->query($sql);

if (!$result) {
    die("Unable to load sell requests. Please check the database.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sell Requests | VighanHarta</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
        }

        h1 {
            background: #101d35;
            color: #ff8c00;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
        }

        .table-container {
            overflow-x: auto;
            background: white;
            padding: 15px;
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #172944;
            color: white;
        }

        tr:nth-child(even) {
            background: #f2f2f2;
        }

        .back {
            display: inline-block;
            margin: 20px 0;
            padding: 12px 18px;
            background: #ff8c00;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .empty {
            text-align: center;
            padding: 20px;
        }

        .action-form {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .action-form button {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
        }

        .accept {
            background: #16803c;
        }

        .reject {
            background: #d93025;
        }

        .status {
            font-weight: bold;
        }

        .success {
            color: #16803c;
            font-weight: bold;
        }

        .error {
            color: #d93025;
            font-weight: bold;
        }
    </style>
</head>

<body>

<h1>VighanHarta - Sell Requests</h1>

<a class="back" href="admin_test.php">← Back to Admin Panel</a>

<?php if (isset($_GET["updated"])): ?>
    <p class="success">Sell request status updated successfully!</p>
<?php endif; ?>

<?php if ($message !== ""): ?>
    <p class="error"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<div class="table-container">

<?php if ($result->num_rows > 0): ?>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Customer Name</th>
            <th>Phone</th>
            <th>Brand</th>
            <th>Model</th>
            <th>Condition</th>
            <th>Expected Price</th>
            <th>Photo</th>
            <th>Details</th>
            <th>Status</th>
            <th>Date</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>

        <tr>
            <td><?= (int)$row["id"] ?></td>

            <td><?= htmlspecialchars($row["customer_name"]) ?></td>

            <td><?= htmlspecialchars($row["phone"]) ?></td>

            <td><?= htmlspecialchars($row["brand"]) ?></td>

            <td><?= htmlspecialchars($row["model"]) ?></td>

            <td><?= htmlspecialchars($row["mobile_condition"]) ?></td>

            <td>₹<?= number_format((float)$row["expected_price"], 2) ?></td>

            <td>
                <?php if (!empty($row["photo"])): ?>
                    <a href="<?= htmlspecialchars($row["photo"]) ?>"
                       target="_blank" rel="noopener noreferrer">
                        <img
                            src="<?= htmlspecialchars($row["photo"]) ?>"
                            alt="Customer mobile photo"
                            width="80"
                            style="height:80px;object-fit:cover;border-radius:6px;"
                        >
                    </a>
                <?php else: ?>
                    No photo
                <?php endif; ?>
            </td>

            <td><?= htmlspecialchars($row["details"] ?? "") ?></td>

            <td class="status">
                <?= htmlspecialchars($row["status"] ?? "Pending") ?>
            </td>

            <td><?= htmlspecialchars($row["created_at"] ?? "") ?></td>

            <td>
                <form method="POST"
                      action="admin_sell_requests.php"
                      class="action-form">

                    <input
                        type="hidden"
                        name="request_id"
                        value="<?= (int)$row["id"] ?>"
                    >

                    <button
                        type="submit"
                        name="action"
                        value="Accepted"
                        class="accept"
                        onclick="return confirm('Accept this sell request?');"
                    >
                        Accept
                    </button>

                    <button
                        type="submit"
                        name="action"
                        value="Rejected"
                        class="reject"
                        onclick="return confirm('Reject this sell request?');"
                    >
                        Reject
                    </button>

                </form>
            </td>
        </tr>

    <?php endwhile; ?>
    </tbody>
</table>

<?php else: ?>

    <p class="empty">No sell requests found.</p>

<?php endif; ?>

</div>

</body>
</html>