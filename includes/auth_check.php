<?php
session_start();

// Prevent session fixation by regenerating session ID on login
if (isset($_SESSION['user_id']) && !isset($_SESSION['session_regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['session_regenerated'] = true;
}

// Function to check if user is authenticated and approved
function restrictAccess($pdo) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    // Fetch user payment status
    $stmt = $pdo->prepare("SELECT payment_status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        // User not found, destroy session and redirect to login
        session_destroy();
        header("Location: login.php");
        exit;
    }

    if ($user['payment_status'] !== 'approved') {
        header("Location: payment.php");
        exit;
    }
}

// Function to generate CSRF token
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Function to validate CSRF token
function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }
    // Regenerate CSRF token after successful validation
    unset($_SESSION['csrf_token']);
}

// Add security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net; img-src 'self'");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Referrer-Policy: no-referrer");
?>
