<?php
session_start();
ob_start();
require_once 'includes/db_connect.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        // Ensure user ID 1 is always admin
        $_SESSION['role'] = ($user['id'] == 1) ? 'admin' : $user['role'];
        $_SESSION['branch'] = $user['branch_id']; // Assuming branch_id is the correct column name
        // Update role in database if user ID is 1
        if ($user['id'] == 1 && $user['role'] !== 'admin') {
            $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = 1");
            $stmt->execute();
        }
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid credentials.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Golden Crop Distributors Ltd. - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body {
            background: linear-gradient(135deg, #f0f8ff, #ffd700); /* Golden gradient */
            overflow: hidden;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .graphics .shape {
            position: absolute;
            animation: float 10s infinite ease-in-out;
            opacity: 0.6;
        }

        .wheat { 
            width: 50px; 
            height: 50px; 
            background: url('https://img.icons8.com/ios/50/000000/wheat.png') no-repeat center; 
            background-size: contain; 
        }

        .crop { 
            width: 60px; 
            height: 60px; 
            background: url('https://img.icons8.com/ios/50/000000/corn.png') no-repeat center; 
            background-size: contain; 
        }

        .sun { 
            width: 40px; 
            height: 40px; 
            background: url('https://img.icons8.com/ios/50/000000/sun.png') no-repeat center; 
            background-size: contain; 
            animation: spin 15s linear infinite; 
        }

        .leaf { 
            width: 50px; 
            height: 50px; 
            background: url('https://img.icons8.com/ios/50/000000/leaf.png') no-repeat center; 
            background-size: contain; 
        }

        .shape:nth-child(1) { top: 10%; left: 15%; }
        .shape:nth-child(2) { top: 20%; right: 20%; }
        .shape:nth-child(3) { bottom: 25%; left: 25%; }
        .shape:nth-child(4) { bottom: 15%; right: 10%; }
        .shape:nth-child(5) { top: 50%; left: 5%; }

        main {
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            z-index: 1;
            position: relative;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: url('https://img.icons8.com/color/80/000000/wheat.png') no-repeat center;
            background-size: contain;
            display: inline-block;
            animation: pulse 2s infinite ease-in-out;
        }

        h2 {
            color: #000080;
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .error {
            color: #dc3545;
            text-align: center;
            margin-bottom: 15px;
        }

        .form-control:focus {
            border-color: #ffd700; /* Golden focus */
            box-shadow: 0 0 5px rgba(255, 215, 0, 0.5);
        }

        .btn-primary {
            background: #000080;
            border: none;
            transition: background 0.3s;
        }

        .btn-primary:hover {
            background: #ffd700; /* Golden hover */
            color: #000080;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>
    <div class="graphics">
        <div class="shape wheat"></div>
        <div class="shape crop"></div>
        <div class="shape sun"></div>
        <div class="shape leaf"></div>
        <div class="shape wheat"></div>
    </div>
    <main>
        <div class="logo-container">
            <div class="logo"></div>
            <h2>Golden Crop Distributors Ltd.</h2>
        </div> <hr>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST" id="loginForm">
            <div class="mb-3">
                <label for="username" class="form-label"><i class="bi bi-person me-2"></i>Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div><hr>
            <div class="mb-3">
                <label for="password" class="form-label"><i class="bi bi-lock me-2"></i>Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div><hr>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php ob_end_flush(); ?>