<?php
echo "PHP Version: " . phpversion() . "<br>";

$conn = new mysqli('localhost', 'your_db_user', 'your_db_pass', 'your_db_name');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Database connected successfully!";
?>