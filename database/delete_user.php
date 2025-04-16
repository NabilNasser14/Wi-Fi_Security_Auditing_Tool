<?php
include 'db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // Reorder IDs sequentially
    $conn->query("SET @count = 0");
    $conn->query("UPDATE users SET id = (@count := @count + 1) ORDER BY id");

    // Reset AUTO_INCREMENT to the next available number
    $result = $conn->query("SELECT MAX(id) AS max_id FROM users");
    $row = $result->fetch_assoc();
    $newAutoIncrement = $row['max_id'] + 1;
    $conn->query("ALTER TABLE users AUTO_INCREMENT = $newAutoIncrement");

    // Redirect back to vault.php
    header("Location: vault.php");
    exit();
}
?>
