<?php
$host = "localhost";
$db_user = "root";
$db_pass = "@Nikesh125";
$db_name = "grade_system";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>