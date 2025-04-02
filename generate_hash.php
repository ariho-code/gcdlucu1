<?php
$password = "code4good"; // The password you want to hash
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Generated Hash: " . $hash;
?>