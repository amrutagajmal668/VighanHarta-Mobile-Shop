  <?php
require_once "db.php";

$sql = "SELECT id, name, category, price, stock, image, description
        FROM products
        WHERE category = 'new-mobile'
        ORDER BY id DESC";

$result = $conn->query($sql);

if (!$result) {
    die("Unable to load products.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New Mobiles | Vighnaharta</title>

<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Arial,sans-serif;background:#f4f6f9;color:#222}
header{background:#10243a;color:white;padding:18px 5%;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px}
header h1{color:#ff9f1c}
nav{display:flex;flex-wrap:wrap;gap:15px}
nav a{color:white;text-decoration:none;font-weight:bold}
nav a:hover{color:#ff9f1c}
.hero{background:#183b59;color:white;text-align:center;padding:45px 15px}
.hero h2{font-size:32px;margin-bottom:12px}
.hero p{line-height:1.7}
.controls{max-width:1100px;margin:25px auto;padding:0 15px;display:flex;gap:12px;flex-wrap:wrap}
input,select{padding:12px;border:1px solid #ccc;border-radius:6px;font-size:15px}
#search{flex:2;min-width:180px}
#priceFilter{flex:1;min-width:150px}
.products{max-width:1100px;margin:auto;padding:0 15px 35px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:22px}
.card{background:white;border-radius:10px;padding:16px;text-align:center;box-shadow:0 3px 12px #00000012}
.phone-image{width:100%;height:190px;object-fit:contain;margin-bottom:12px}
.card h3{margin-bottom:10px;color:#10243a}
.condition{display:inline-block;background:#e4f7e8;color:#167334;padding:5px 10px;border-radius:15px;font-size:12px;margin-bottom:10px}
.details{color:#555;font-size:14px;line-height:1.8}
.price{color:#e87500;font-size:22px;font-weight:bold;margin:12px 0}
.stock{margin:10px 0;font-size:14px}
button{background:#ff9f1c;border:none;color:#10243a;padding:11px 16px;border-radius:6px;cursor:pointer;font-size:15px;font-weight:bold}
button:hover{background:#ed8c08}
button:disabled{background:#ccc;cursor:not-allowed}
.cart-bar{background:#e7edf3;padding:18px;text-align:center;margin-bottom:25px}
.cart-bar button{margin-left:10px}
.pickup{background:white;text-align:center;padding:25px 15px;line-height:1.8}
footer{background:#10243a;color:white;text-align:center;padding:20px;line-height:1.8}
@media(max-width:600px){header{justify-content:center;text-align:center}nav{justify-content:center}.hero h2{font-size:25px}}
</style>
</head>

<body>

<header>
    <h1>Vighanharta</h1>
    <nav>
        <a href="index.html">Home</a>
        <a href="used-mobile.php">Used Mobiles</a>
        <a href="new-mobile.php">New Mobiles</a>
        <a href="accessories.html">Accessories</a>
        <a href="repair.html">Repairing</a>
        <a href="card.html">My Cart</a>
    </nav>
</header>

<section class="hero">
    <h2>Latest New Mobiles</h2>
    <p>Explore the latest smartphones and basicphones at affordable price.</p>
    <p>Order online and pick up from our store.</p>
</section>

<div class="controls">
    <input type="text" id="search" placeholder="Search mobile name..."
           oninput="filterProducts()">

    <select id="priceFilter" onchange="filterProducts()">
        <option value="all">All Prices</option>
        <option value="10000">Below ₹10,000</option>
        <option value="20000">Below ₹20,000</option>
        <option value="30000">Below ₹30,000</option>
    </select>
</div>

<div class="cart-bar">
    <strong>Cart Items: <span id="cartCount">0</span></strong>
    <button onclick="viewCart()">View Cart</button>
</div>

<main class="products" id="products">

<?php if ($result->num_rows > 0): ?>

    <?php while ($product = $result->fetch_assoc()): ?>
        <div class="card"
             data-name="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"
             data-price="<?= (float)$product['price'] ?>">

            <?php if (!empty($product['image'])): ?>
                <img class="phone-image"
                     src="<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>"
                     alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"
                     onerror="this.style.display='none'">
            <?php else: ?>
                <div class="phone-image"
                     style="display:flex;align-items:center;justify-content:center">
                    Mobile Image
                </div>
            <?php endif; ?>

            <h3><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h3>

            <span class="condition">Brand New Mobile</span>

            <p class="details">
                <?= htmlspecialchars($product['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </p>

            <p class="price">
                ₹<?= number_format((float)$product['price'], 0) ?>
            </p>

            <p class="stock">
                <?php if ((int)$product['stock'] > 0): ?>
                    Stock: <?= (int)$product['stock'] ?>
                <?php else: ?>
                    Out of Stock
                <?php endif; ?>
            </p>

            <button
                onclick="addToCart(
                    <?= (int)$product['id'] ?>,
                    '<?= htmlspecialchars(addslashes($product['name']), ENT_QUOTES, 'UTF-8') ?>',
                    <?= (float)$product['price'] ?>,
                    <?= (int)$product['stock'] ?>
                )"
                <?= (int)$product['stock'] <= 0 ? 'disabled' : '' ?>>
                Add to Cart
            </button>
        </div>
    <?php endwhile; ?>

<?php else: ?>
    <p>No used mobiles available right now.</p>
<?php endif; ?>

</main>

<section class="pickup">
    <h2>Easy Store Pickup</h2>
    <p>Select your mobile online and collect it from our shop.</p>
    <p>Home delivery is not available.</p>
</section>

<footer>
    <h3>Vighnaharta Mobile Shop</h3>
    <p>New Mobiles | Used Mobiles | Accessories | Repairing</p>
    <p>© 2026 Vighnaharta. All rights reserved.</p>
</footer>

<script>
const CART_KEY = "viganHartaCart";

function getCart() {
    try {
        const cart = JSON.parse(localStorage.getItem(CART_KEY) || "[]");
        return Array.isArray(cart) ? cart : [];
    } catch (e) {
        return [];
    }
}

function updateCartCount() {
    const cart = getCart();
    document.getElementById("cartCount").textContent =
        cart.reduce((total, item) => total + Number(item.quantity || 0), 0);
}

function addToCart(id, name, price, stock) {
    const cart = getCart();
    const existing = cart.find(item => Number(item.id) === Number(id));

    if (existing) {
        if (Number(existing.quantity) >= Number(stock)) {
            alert("Maximum available stock reached.");
            return;
        }
        existing.quantity = Number(existing.quantity) + 1;
    } else {
        cart.push({
            id: Number(id),
            productId:Number(id),
            name: name,
            price: Number(price),
            quantity: 1
        });
    }

    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    updateCartCount();
    alert("Mobile added to cart!");
}

function viewCart() {
    const cart = getCart();

    if (cart.length === 0) {
        alert("Your cart is empty.");
        return;
    }

    let message = "Your Cart:\n\n";
    let total = 0;

    cart.forEach(item => {
        message += item.name + " x " + item.quantity +
            " = ₹" + (item.price * item.quantity) + "\n";
        total += item.price * item.quantity;
    });

    message += "\nTotal: ₹" + total.toLocaleString("en-IN");
    alert(message);
}

function filterProducts() {
    const search = document.getElementById("search").value.toLowerCase();
    const limit = document.getElementById("priceFilter").value;

    document.querySelectorAll(".card").forEach(card => {
        const name = card.dataset.name.toLowerCase();
        const price = Number(card.dataset.price);

        const matchesName = name.includes(search);
        const matchesPrice = limit === "all" || price < Number(limit);

        card.style.display = matchesName && matchesPrice ? "" : "none";
    });
}

updateCartCount();
</script>

</body>
</html>
