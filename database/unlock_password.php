<?php
session_start();
include 'db.php';

define("SECRET_KEY", "unlock_passwords_key");

if (!isset($_SESSION["admin_access"]) || $_SESSION["admin_access"] !== true) {
    die("Unauthorized.");
}

if ($_POST["secret_key"] !== SECRET_KEY) {
    die("Invalid Secret Key.");
}

$id = intval($_POST["id"]);
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($password);
$stmt->fetch();
$stmt->close();

echo $password;
?>
