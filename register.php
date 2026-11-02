<?php
require_once "db.php";

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if ($name === "" || $email === "" || $password === "") {
        $message = "Please fill all required fields.";
        $messageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } elseif ($phone !== "" && !preg_match("/^[6-9][0-9]{9}$/", $phone)) {
        $message = "Please enter a valid 10-digit mobile number.";
        $messageType = "error";
    } elseif (strlen($password) < 8) {
        $message = "Password must contain at least 8 characters.";
        $messageType = "error";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
        $messageType = "error";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "This email is already registered.";
            $messageType = "error";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users (full_name, email, phone, password)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "ssss",
                $name,
                $email,
                $phone,
                $hashedPassword
            );

            if ($stmt->execute()) {
                $message = "Registration successful! You can now log in.";
                $messageType = "success";
            } else {
                $message = "Registration failed. Please try again.";
                $messageType = "error";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | ViganHarta</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            color: #102541;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .register-box {
            background: white;
            width: 100%;
            max-width: 420px;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            margin-top: 0;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin: 14px 0 6px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }

        button {
            width: 100%;
            padding: 13px;
            margin-top: 22px;
            background: #ff8c00;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #e67e00;
        }

        .message {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            overflow-wrap: anywhere;
        }

        .success {
            background: #e4f7e9;
            color: #176b2c;
        }

        .error {
            background: #fde8e8;
            color: #a32121;
        }

        .login-link {
            text-align: center;
            margin-top: 18px;
        }

        a {
            color: #e67e00;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="register-box">
        <h1>VighanHarta</h1>
        <p class="subtitle">Create Your Customer Account</p>

        <?php if ($message !== ""): ?>
            <div class="message <?= htmlspecialchars($messageType) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <label for="full_name">Full Name *</label>
            <input
                type="text"
                id="full_name"
                name="full_name"
                maxlength="100"
                required
            >

            <label for="email">Email Address *</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="100"
                required
            >

            <label for="phone">Mobile Number</label>
            <input
                type="tel"
                id="phone"
                name="phone"
                pattern="[6-9][0-9]{9}"
                maxlength="10"
                placeholder="10-digit mobile number"
            >

            <label for="password">Password *</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                required
            >

            <label for="confirm_password">Confirm Password *</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="8"
                required
            >

            <button type="submit">Create Account</button>
        </form>

        <p class="login-link">
            Already registered?
            <a href="login.php">Login Here</a>
        </p>
    </div>
</body>
</html>