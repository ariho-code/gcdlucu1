<?php
$host = 'localhost';
$dbname = 'gcdl_cropsync';
$username = 'root'; // Update with your MySQL username
$password = '';     // Update with your MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>