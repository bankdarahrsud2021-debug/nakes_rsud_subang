<?php

$password = "admin123";

$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h3>Password Hash:</h3>";
echo "<p>" . htmlspecialchars($hash) . "</p>";

echo "<hr>";
echo "<p>Username: admin</p>";
echo "<p>Password: admin123</p>";