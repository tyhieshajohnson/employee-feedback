<?php
$conn = new mysqli("localhost", "root", "", "employee_feedback");

if ($conn->connect_error) {
    die("Connection failed");
}
?>
