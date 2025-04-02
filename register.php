<?php
session_start();
include 'includes/db_connect.php';

$referrer_username = 'N/A';
if (isset($_GET['ref'])) {
    $stmt = $pdo->prepare("SELECT username FROM users WHERE referral_code = ?");
    $stmt->execute([$_GET['ref']]);
    $referrer = $stmt->fetch();
    $referrer_username = $referrer ? htmlspecialchars($referrer['username']) : 'N/A';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $country = $_POST['country'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $referral_code = substr(md5(uniqid()), 0, 10);
    $referred_by = isset($_GET['ref']) && $referrer ? $_GET['ref'] : null;

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, country, phone, password, referral_code, referred_by, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$username, $email, $country, $phone, $password, $referral_code, $referred_by]);
        $user_id = $pdo->lastInsertId();
        if ($referred_by) {
            $pdo->prepare("INSERT INTO referrals (user_id, referred_user_id) SELECT id, ? FROM users WHERE referral_code = ?")->execute([$user_id, $referred_by]);
        }
        $_SESSION['user_id'] = $user_id;
        header("Location: payment.php");
        exit;
    } catch (PDOException $e) {
        $error = $e->getCode() == 23000 ? (strpos($e->getMessage(), 'username') !== false ? "Username already taken." : "Email already registered.") : "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - SMART QASH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="background: linear-gradient(135deg, #ff6b6b, #4ecdc4); margin: 0; height: 100vh; display: flex; justify-content: center; align-items: center;">
    <div class="card p-4 shadow" style="max-width: 300px; width: 100%; border-radius: 15px; background: rgba(255, 255, 255, 0.9); color: #4ecdc4;">
        <h2 class="text-center mb-4" style="color: #ff6b6b;">Sign Up</h2>
        <?php if (isset($error)) echo "<p class='text-danger text-center'>$error</p>"; ?>
        <p class="text-center text-success">Referred by: <?php echo $referrer_username; ?></p>
        <form action="register.php<?php echo isset($_GET['ref']) ? '?ref=' . urlencode($_GET['ref']) : ''; ?>" method="POST">
            <div class="mb-3"><input type="text" name="username" class="form-control" placeholder="Username" style="border-color: #4ecdc4;" required></div>
            <div class="mb-3"><input type="email" name="email" class="form-control" placeholder="Email" style="border-color: #4ecdc4;" required></div>
            <div class="mb-3">
                <select name="country" class="form-control" style="border-color: #4ecdc4;" required>
                    <option value="">Select Country</option>
                    <option value="Uganda">Uganda</option>
                    <option value="Kenya">Kenya</option>
                    <option value="Tanzania">Tanzania</option>
                </select>
            </div>
            <div class="mb-3"><input type="tel" name="phone" class="form-control" placeholder="Phone" style="border-color: #4ecdc4;" required></div>
            <div class="mb-3"><input type="password" name="password" class="form-control" placeholder="Password" style="border-color: #4ecdc4;" required></div>
            <div class="mb-3"><input type="password" name="retype_password" class="form-control" placeholder="Retype Password" style="border-color: #4ecdc4;" required></div>
            <button type="submit" class="btn btn-custom w-100" style="background: #ff6b6b; color: #fff; border-radius: 10px;">Sign Up <span>➡</span></button>
            <p class="text-center mt-3" style="color: #4ecdc4;">Already have an account? <a href="login.php" style="color: #ff6b6b;">Sign In</a></p>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>