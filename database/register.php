<?php
include 'db.php';
$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($username) || empty($email) || empty($password)) {
        $error_message = "All fields are required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format!";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        $usernameExists = $stmt->num_rows > 0;
        $stmt->close();

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $emailExists = $stmt->num_rows > 0;
        $stmt->close();

        if ($usernameExists && $emailExists) {
            $error_message = "Username and Email are already registered!";
        } elseif ($usernameExists) {
            $error_message = "Username is already registered!";
        } elseif ($emailExists) {
            $error_message = "Email is already registered!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);

            if ($stmt->execute()) {
                $success_message = "Registration successful!<br><small style='color:gray;'>Redirecting to login...</small>";
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 3000);
                </script>";
            } else {
                $error_message = "Error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | YSAT</title>
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

        .register-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 100%;
            max-width: 400px;
            animation: fadeIn 1s ease-in-out;
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

        .btn-register {
            border-radius: 50px;
            padding: 10px;
            font-size: 16px;
            transition: 0.3s;
        }

        .btn-register:hover {
            background-color: #6610f2;
        }

        .alert {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            text-align: center;
        }

        .password-policy {
            margin-top: 10px;
            font-size: 14px;
            text-align: left;
            color: #e74c3c;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2 class="mb-4">Register for YSAT</h2>

        <!-- Success Message -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?= $success_message; ?></div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= $error_message; ?></div>
        <?php endif; ?>

        <form method="post" onsubmit="return validatePasswordPolicy()">
    <div class="form-group">
        <i class="fas fa-user"></i>
        <input type="text" id="username" name="username" class="form-control ps-4" placeholder="Name" required onclick="trackClick('username')" autocomplete="off">
    </div>
    <div class="form-group">
        <i class="fas fa-envelope"></i>
        <input type="email" id="email" name="email" class="form-control ps-4" placeholder="Email" required onclick="trackClick('email')" autocomplete="off">
    </div>
    <div class="form-group">
        <i class="fas fa-lock"></i>
        <input type="password" id="password" name="password" class="form-control ps-4" placeholder="Password" required onclick="trackClick('password')" autocomplete="new-password" onkeyup="checkPasswordPolicy()">
    </div>
    <button type="submit" class="btn btn-primary btn-register w-100" onclick="trackClick('register')">Register</button>

    <!-- Password policy reminders -->
    <div id="password-policy" class="password-policy"></div>
</form>

        <p class="mt-3">Already have an account? <a href="login.php">Login</a></p>
    </div>

    <script>
        // Password policy validation
        function checkPasswordPolicy() {
            const password = document.getElementById('password').value;
            const policyElement = document.getElementById('password-policy');
            let message = "";

            const minLength = /.{8,}/;
            const hasUppercase = /[A-Z]/;
            const hasLowercase = /[a-z]/;
            const hasNumber = /\d/;
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/;

            if (!minLength.test(password)) {
                message += "<p>✖ At least 8 characters</p>";
            }
            if (!hasUppercase.test(password)) {
                message += "<p>✖ At least one uppercase letter</p>";
            }
            if (!hasLowercase.test(password)) {
                message += "<p>✖ At least one lowercase letter</p>";
            }
            if (!hasNumber.test(password)) {
                message += "<p>✖ At least one number</p>";
            }
            if (!hasSpecial.test(password)) {
                message += "<p>✖ At least one special character (@, #, $, etc.)</p>";
            }

            if (message === "") {
                message = "<p>✔ Password meets all the requirements</p>";
                policyElement.style.color = "green";
            } else {
                policyElement.style.color = "#e74c3c";
            }

            policyElement.innerHTML = message;
        }

        // Form validation before submission
        function validatePasswordPolicy() {
            const password = document.getElementById('password').value;
            const minLength = /.{8,}/;
            const hasUppercase = /[A-Z]/;
            const hasLowercase = /[a-z]/;
            const hasNumber = /\d/;
            const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/;

            if (!minLength.test(password) || 
                !hasUppercase.test(password) || 
                !hasLowercase.test(password) || 
                !hasNumber.test(password) || 
                !hasSpecial.test(password)) {
                    alert("Password does not meet all the policy requirements.");
                    return false; 
            }
            return true; 
        }
    </script>
</body>
</html>
