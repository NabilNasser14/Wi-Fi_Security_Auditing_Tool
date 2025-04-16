<?php
session_start();
include 'db.php';

define("SPECIAL_KEY", "my_secure_admin_key");

if (!isset($_SESSION["admin_access"]) || $_SESSION["admin_access"] !== true) {
    header("Location: vault.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["user_id"], $_POST["new_password"], $_POST["admin_key"])) {
    $user_id = intval($_POST["user_id"]);
    $admin_key = $_POST["admin_key"];
    $new_password = password_hash($_POST["new_password"], PASSWORD_DEFAULT); 

    if ($admin_key !== SPECIAL_KEY) {
        $_SESSION["message"] = "❌ Invalid Admin Key! Access Denied.";
        $_SESSION["message_type"] = "danger"; 
        header("Location: vault.php");
        exit();
    }

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $new_password, $user_id);

    if ($stmt->execute()) {
        $_SESSION["message"] = "✅ Password reset successfully!";
        $_SESSION["message_type"] = "success";
    } else {
        $_SESSION["message"] = "⚠️ Error resetting password.";
        $_SESSION["message_type"] = "warning";
    }

    $stmt->close();
    $conn->close();

    header("Location: vault.php");
    exit();
}
?>
