<?php
include "db.php";

$name = $_POST['name'];
$dept = $_POST['department'];
$feedback = $_POST['feedback'];

$sql = "INSERT INTO feedback (employee_name, department, feedback)
        VALUES ('$name', '$dept', '$feedback')";

$conn->query($sql);

$result = $conn->query("SELECT * FROM feedback ORDER BY id DESC");

while ($row = $result->fetch_assoc()) {
    echo "<p><strong>{$row['employee_name']}</strong> ({$row['department']}): {$row['feedback']}</p>";
}
?>