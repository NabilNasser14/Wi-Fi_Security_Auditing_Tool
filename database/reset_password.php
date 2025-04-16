<?php
session_start();
include 'db.php';

if (!isset($_SESSION["admin_access"]) || $_SESSION["admin_access"] !== true) {
    header("Location: vault.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "Invalid request.";
    exit();
}

$user_id = intval($_GET['id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reset Password | YSAT</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #0044cc, #8a2be2);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            color: white;
        }
        .reset-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.5s ease-in-out;
        }
        .form-control {
            border-radius: 50px;
            padding: 10px 15px;
            font-size: 16px;
        }
        .btn-reset {
            border-radius: 50px;
            padding: 10px;
            font-size: 16px;
            transition: 0.3s;
            background-color: #FFA64D; 
            border: none;
            color: white;
        }
        .btn-reset:hover {
            background-color: #CC5500; 
        }

        .btn-cancel {
            border-radius: 50px;
            padding: 10px;
            font-size: 16px;
            transition: 0.3s;
            background-color: #808080; 
            border: none;
            color: white;
        }
        .btn-cancel:hover {
            background-color: #606060; 
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .password-requirements {
            font-size: 14px;
            color: #333;
            text-align: left;
            margin-top: 5px;
        }
        .valid {
            color: green;
        }
        .invalid {
            color: red;
        }
    </style>
    <script>
        function validatePassword() {
            let password = document.getElementById("new_password").value;
            let length = document.getElementById("length");
            let uppercase = document.getElementById("uppercase");
            let lowercase = document.getElementById("lowercase");
            let number = document.getElementById("number");
            let special = document.getElementById("special");

            function updateRequirement(element, condition) {
                if (condition) {
                    element.innerHTML = "✔ " + element.dataset.text;
                    element.classList.add("valid");
                    element.classList.remove("invalid");
                } else {
                    element.innerHTML = "✖ " + element.dataset.text;
                    element.classList.add("invalid");
                    element.classList.remove("valid");
                }
            }

            updateRequirement(length, password.length >= 8);
            updateRequirement(uppercase, /[A-Z]/.test(password));
            updateRequirement(lowercase, /[a-z]/.test(password));
            updateRequirement(number, /\d/.test(password));
            updateRequirement(special, /[@#$%^&*!]/.test(password));

            return password.length >= 8 && /[A-Z]/.test(password) && /[a-z]/.test(password) && /\d/.test(password) && /[@#$%^&*!]/.test(password);
        }

        function handleFormSubmit(event) {
            if (!validatePassword()) {
                event.preventDefault();  
                alert("Password does not meet all requirements. Please fix the issues.");
            }
        }
    </script>
</head>
<body>
    <div class="reset-container">
        <h2 class="fw-bold text-dark">Reset Password</h2>
        <form action="reset_password_process.php" method="post" onsubmit="handleFormSubmit(event)">
            <input type="hidden" name="user_id" value="<?= $user_id ?>">
            <div class="mb-3">
                <input type="password" name="admin_key" class="form-control" placeholder="Enter Admin Key" required>
            </div>
            <div class="mb-3">
    <input type="password" id="new_password" name="new_password" class="form-control" placeholder="New Password" required onkeyup="validatePassword()">
</div>
<div class="password-requirements mt-3">
    <p id="length" data-text="At least 8 characters" class="invalid">✖ At least 8 characters</p>
    <p id="uppercase" data-text="At least one uppercase letter" class="invalid">✖ At least one uppercase letter</p>
    <p id="lowercase" data-text="At least one lowercase letter" class="invalid">✖ At least one lowercase letter</p>
    <p id="number" data-text="At least one number" class="invalid">✖ At least one number</p>
    <p id="special" data-text="At least one special character (@, #, $, etc.)" class="invalid">✖ At least one special character (@, #, $, etc.)</p>
</div>

            <button type="submit" class="btn btn-reset w-100">Reset Password</button>
            <a href="vault.php" class="btn btn-cancel w-100 mt-2">Cancel</a>
        </form>
    </div>
</body>
</html>
