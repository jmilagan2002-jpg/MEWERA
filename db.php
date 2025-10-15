<?php
$servername = "localhost";   // default XAMPP host
$username = "root";          // default XAMPP username
$password = "";              // default XAMPP password (empty)
$database = "cap_store";     // the database name you created in phpMyAdmin

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
