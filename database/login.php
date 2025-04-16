<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $username, $hashed_password);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            $_SESSION["user_id"] = $id;
            $_SESSION["username"] = $username;
            header("Location: http://localhost:5000/");
            exit();
        } else {
            $error_message = "Invalid credentials!";
        }
    } else {
        $error_message = "No user found!";
    }
    $stmt->close();
    $conn->close();
}

// Admin Vault Access
define("SPECIAL_KEY", "my_secure_admin_key");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["admin_key"])) {
    if ($_POST["admin_key"] === SPECIAL_KEY) {
        $_SESSION["admin_access"] = true;
        header("Location: vault.php");
        exit();
    } else {
        $_SESSION["admin_error"] = "Invalid Admin Key!";
        $_SESSION["show_admin_form"] = true;
        header("Location: login.php"); 
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | YSAT</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        body {
            background: linear-gradient(135deg, #007bff, #6610f2);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: 'Poppins', sans-serif;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 100%;
            max-width: 400px;
            animation: fadeIn 1s ease-in-out;
            position: relative;
        }
        .form-control {
            border-radius: 50px;
            padding: 10px 15px;
            font-size: 16px;
        }
        .form-group {
            position: relative;
            margin-bottom: 1rem;
        }
        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #555;
        }
        .btn-login {
            border-radius: 50px;
            padding: 10px;
            font-size: 16px;
            transition: 0.3s;
        }
        .btn-login:hover {
            background-color: #6610f2;
        }
        .alert {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .hidden-admin {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
            display: none;
        }
        .show-admin {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="login-container" id="login-card">
        <h2 class="mb-4">Login to YSAT</h2>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?= $error_message; ?></div>
        <?php endif; ?>

        <form method="post">
    <div class="form-group">
        <i class="fas fa-envelope"></i>
        <input type="email" id="email" name="email" class="form-control ps-4" placeholder="Email" required onclick="trackClick('email')" autocomplete="off">
    </div>
    <div class="form-group">
        <i class="fas fa-lock"></i>
        <input type="password" id="password" name="password" class="form-control ps-4" placeholder="Password" required onclick="trackClick('password')" autocomplete="new-password">
    </div>
    <button type="submit" class="btn btn-primary btn-login w-100" onclick="trackClick('login')">Login</button>
</form>
        
        <p class="mt-3">Don't have an account? <a href="register.php">Register</a></p>

        <?php if (isset($_SESSION["admin_error"])): ?>
    <div class="alert alert-danger" id="admin-error">
        <?= $_SESSION["admin_error"]; ?>
    </div>
<?php endif; ?>

<!-- Admin Vault Form -->
<form id="admin-form" method="post" class="mt-3" 
    style="<?= isset($_SESSION["show_admin_form"]) ? 'display:block;' : 'display:none;' ?>">
    <div class="form-group mb-3"> 
        <input type="password" name="admin_key" class="form-control" placeholder="Enter Secret Key" required>
    </div>
    <button type="submit" class="btn btn-danger w-100">Access Vault</button>
</form>


<?php 
    unset($_SESSION["admin_error"]); 
    unset($_SESSION["show_admin_form"]); 
?>

    <script>
        let sequence = [];
        let keySequence = [];
        const unlockPattern = ["email", "password", "login", "email"];
        const unlockKeys = ["Shift", "A", "D", "M", "I", "N"];

        function trackClick(element) {
            sequence.push(element);
            if (sequence.length > 4) sequence.shift(); 

            if (JSON.stringify(sequence) === JSON.stringify(unlockPattern)) {
                unlockVault();
            }
        }

        document.addEventListener("keydown", function(event) {
            keySequence.push(event.key);
            if (keySequence.length > 6) keySequence.shift(); 

            if (JSON.stringify(keySequence) === JSON.stringify(unlockKeys)) {
                unlockVault();
            }
        });

        function unlockVault() {
            let adminForm = document.getElementById("admin-form");

            adminForm.style.display = "block";
            setTimeout(() => {
                adminForm.classList.add("show-admin");
            }, 10); 
        }
        document.addEventListener("DOMContentLoaded", function() {
        let errorBox = document.getElementById("admin-error");
        let adminForm = document.getElementById("admin-form");

        if (errorBox) {
            setTimeout(() => {
                errorBox.style.display = "none"; 
                adminForm.style.display = "block"; 
            }, 3000); 
        }
    });
    </script>
</body>
</html>
