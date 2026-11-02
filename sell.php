 
 <?php
require_once "db.php";
require_once "sms_config.php";

function sendShopSMS($message) {
    $url = "https://www.fast2sms.com/dev/bulkV2";

    $data = [
        "route" => "q",
        "message" => $message,
        "numbers" => SHOP_OWNER_PHONE
    ];

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => [
            "authorization: " . FAST2SMS_API_KEY
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $response !== false && $httpCode >= 200 && $httpCode < 300;
}
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $brand = trim($_POST["brand"] ?? "");
    $model = trim($_POST["model"] ?? "");
    $condition = trim($_POST["condition"] ?? "");
    $price = filter_var(
        $_POST["price"] ?? "",
        FILTER_VALIDATE_FLOAT
    );
    $details = trim($_POST["details"] ?? "");

    $photoPath = null;
    $uploadedFile = null;

    if (
        $name === "" ||
        !preg_match('/^[6-9][0-9]{9}$/', $phone) ||
        $brand === "" ||
        $model === "" ||
        $condition === "" ||
        $price === false ||
        $price < 0
    ) {
        $message = "Please enter valid details.";

    } else {

        // Handle optional photo upload
        if (
            isset($_FILES["photo"]) &&
            $_FILES["photo"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {
            $file = $_FILES["photo"];

            if ($file["error"] !== UPLOAD_ERR_OK) {
                $message = "Photo upload failed. Please try again.";

            } elseif ($file["size"] > 1024 * 1024) {
                $message = "Photo size must be less than 1 MB.";

            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file["tmp_name"]);

                $allowedTypes = [
                    "image/jpeg" => "jpg",
                    "image/png"  => "png",
                    "image/webp" => "webp"
                ];

                if (
                    !isset($allowedTypes[$mime]) ||
                    getimagesize($file["tmp_name"]) === false
                ) {
                    $message = "Please upload a JPG, PNG, or WEBP image.";

                } else {
                    $uploadDirectory = __DIR__ . "/uploads/";

                    if (!is_dir($uploadDirectory)) {
                        $message = "Uploads folder was not found.";
                    } else {
                        $fileName = bin2hex(random_bytes(16))
                            . "." . $allowedTypes[$mime];

                        $destination = $uploadDirectory . $fileName;

                        if (move_uploaded_file(
                            $file["tmp_name"],
                            $destination
                        )) {
                            $photoPath = "uploads/" . $fileName;
                            $uploadedFile = $destination;
                        } else {
                            $message = "Unable to save photo. Please try again.";
                        }
                    }
                }
            }
        }

        // Save request when validation and photo upload succeed
        if ($message === "") {

            $stmt = $conn->prepare(
                "INSERT INTO sell_requests
                (customer_name, phone, brand, model,
                mobile_condition, expected_price, photo, details)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$stmt) {
                $message = "Database error: " . $conn->error;
            } else {
                $stmt->bind_param(
                    "sssssdss",
                    $name,
                    $phone,
                    $brand,
                    $model,
                    $condition,
                    $price,
                    $photoPath,
                    $details
                );

            if ($stmt->execute()) {

    $message = "Your selling request has been submitted successfully!";

    $smsText = "New VighanHarta Sell Request: "
        . $name . ", "
        . $brand . " " . $model
        . ", Expected Price Rs." . $price
        . ". Please check Admin Panel.";

    sendShopSMS($smsText);

} else {

    $message = "Database error: " . $stmt->error;

    if ($uploadedFile && file_exists($uploadedFile)) {
        unlink($uploadedFile);
    }
}

$stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell Old Mobile | VighanHarta</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #222;
        }

        header {
            background: #101d35;
            color: white;
            text-align: center;
            padding: 22px;
        }

        header h1 {
            margin: 0;
            color: #ff8c00;
        }

        nav {
            background: #172944;
            text-align: center;
            padding: 14px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin: 8px;
            font-weight: bold;
        }

        .container {
            max-width: 650px;
            background: white;
            margin: 30px auto;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px #ddd;
        }

        h2 {
            text-align: center;
            color: #101d35;
        }

        p {
            text-align: center;
            color: #555;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            margin-top: 7px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }

        button {
            width: 100%;
            background: #ff8c00;
            color: white;
            border: none;
            padding: 14px;
            margin-top: 22px;
            border-radius: 6px;
            font-size: 17px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #e87900;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #101d35;
            text-decoration: none;
            font-weight: bold;
        }

        footer {
            background: #101d35;
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: 30px;
        }
    </style>
</head>

<body>

<header>
    <h1>VighanHarta</h1>
    <p style="color:white;">Buy • Sell • Repair</p>
</header>

<nav>
    <a href="index.html">Home</a>
    <a href="login.php">Login</a>
</nav>

<div class="container">

    <h2>Sell Your Old Mobile</h2>
<?php if ($_SERVER["REQUEST_METHOD"] === "POST"): ?>
    <div style="
        background:#d4edda;
        color:#155724;
        padding:15px;
        margin:15px 0;
        border-radius:8px;
        text-align:center;
        font-weight:bold;
    ">
        <?= htmlspecialchars($message ?: "Your request has been saved successfully!") ?>
    </div>
<?php endif; ?>

    <p>Enter your mobile details to request a selling enquiry.</p>

    <form id="sellForm" method="POST" action="sell.php" enctype="multipart/form-data">

        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" placeholder="Enter your full name" required>

        <label for="phone">Contact Number</label>
        <input type="tel" id="phone" name="phone"
               placeholder="Enter 10-digit mobile number"
               pattern="[0-9]{10}" maxlength="10" required>

        <label for="brand">Mobile Brand</label>
        <select id="brand" name="brand" required>
            <option value="">Select Brand</option>
            <option>Apple iPhone</option>
            <option>Samsung</option>
            <option>OnePlus</option>
            <option>Redmi</option>
            <option>Realme</option>
            <option>Vivo</option>
            <option>Oppo</option>
            <option>Motorola</option>
            <option>Other</option>
        </select>

        <label for="model">Mobile Model</label>
        <input type="text" id="model" name="model"
               placeholder="Example: iPhone 13"
               required>

        <label for="condition">Mobile Condition</label>
        <select id="condition" name="condition" required>
            <option value="">Select Condition</option>
            <option value="Excellent">Excellent</option>
            <option value="Good">Good</option>
            <option value="Average">Average</option>
            <option value="Needs Repair">Needs Repair</option>
        </select>

        <label for="price">Expected Price (₹)</label>
        <input type="number" id="price" name="price"
               placeholder="Enter expected price"
               min="0" required>

        <label for="photo">Upload Mobile Photo</label>
        <input type="file" id="photo" name="photo"
               accept="image/*">

        <label for="details">Additional Details(Optional)</label>
        <textarea id="details" name="details" rows="4"
                  placeholder="Describe your mobile condition or any problems"></textarea>

        <button type="submit">Submit Enquiry</button>

    </form>

    <a class="back" href="index.html">← Back to Home</a>

</div>

<footer>
    <p style="color:white;">© 2026 VighanHarta. All Rights Reserved.</p>
</footer>

 
</body>
</html>
