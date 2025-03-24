<?php
$host = "localhost";
$dbname = "promo_platform"; // your database name
$username = "root"; // your database username
$password = "";     // your database password

// Create a MySQLi connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    // If connection fails, display the error message
    die("Connection failed: " . $conn->connect_error);
}

// Optionally, you can set the character set to UTF-8 to avoid encoding issues
$conn->set_charset("utf8");
?>
