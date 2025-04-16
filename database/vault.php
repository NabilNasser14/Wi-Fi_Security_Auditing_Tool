<?php
session_start();
include 'db.php';

// Define special admin key
define("SPECIAL_KEY", "my_secure_admin_key");
define("SECRET_KEY", "unlock_passwords_key"); 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["admin_key"])) {
    if ($_POST["admin_key"] === SPECIAL_KEY) {
        $_SESSION["admin_access"] = true;
        header("Location: vault.php");
        exit();
    } else {
        echo "<script>alert('Invalid Admin Key!');</script>";
    }
}

if (!isset($_SESSION["admin_access"]) || $_SESSION["admin_access"] !== true) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Vault | YSAT</title>
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
        .vault-container {
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
        .btn-submit {
            border-radius: 50px;
            padding: 10px;
            font-size: 16px;
            transition: 0.3s;
            background-color: #ff6600;
            border: none;
            color: white;
        }
        .btn-submit:hover {
            background-color: #cc5500;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="vault-container">
        <h2 class="fw-bold text-dark">Admin Vault | YSAT</h2>
        <form method="post">
            <div class="mb-3">
                <input type="password" name="admin_key" class="form-control" placeholder="Enter Special Key" required>
            </div>
            <button type="submit" class="btn btn-submit w-100">Access Vault</button>
        </form>
    </div>
</body>
</html>
<?php
    exit();
}

$stmt = $conn->prepare("SELECT id, username, email FROM users");
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($id, $username, $email);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Vault</title>
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
        .vault-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 100%;
            max-width: 900px;
            animation: fadeIn 0.5s ease-in-out;
        }
        .table {
            width: 100%;
            margin-top: 15px;
        }
        .table thead {
            background-color: #333;
            color: white;
        }
        .btn-logout {
            border-radius: 50px;
            padding: 12px;
            font-size: 18px;
            font-weight: bold;
            color: white;
            border: none;
            position: relative;
            overflow: hidden;
            transition: 0.5s ease-in-out;
            background: linear-gradient(90deg, #28a745, #20c997); 
        }
        .btn-logout::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, #ff0000, #cc0000); 
            transition: 0.5s ease-in-out;
        }
        .btn-logout:hover::before {
            left: 0;
        }
        .btn-logout span {
            position: relative;
            z-index: 1;
        }
.btn-common {
    border-radius: 50px;
    font-size: 14px;  
    font-weight: bold;
    color: white;
    border: none;
    transition: 0.3s ease-in-out;
    padding: 6px 12px;  
    width: auto;  
    height: auto;  
}

.table td {
    vertical-align: middle;  
}

.btn-delete {
    background-color: #dc3545; 
}

.btn-delete:hover {
    background-color: #dc3545; 
    animation: bounce 0.6s ease;
}

.btn-unlock {
    background-color: #007bff; 
}

.btn-unlock:hover {
    background-color: #007bff; 
    animation: bounce 0.6s ease;
}

.btn-warning {
    background-color: #ffc107; 
}

.btn-warning:hover {
    background-color: #ffc107; 
    animation: bounce 0.6s ease;
}

.btn-common::after {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.2);
    transition: 0.4s ease-in-out;
}

.btn-common:hover {
    animation: bounce 0.2s ease;
}

    </style>
    <script>
        function deleteUser(userId) {
            if (confirm("Are you sure you want to delete this user?")) {
                window.location.href = "delete_user.php?id=" + userId;
            }
        }

        function unlockPassword(userId) {
            let secretKey = prompt("Enter Secret Key to Unlock Password:");
            if (secretKey) {
                fetch("unlock_password.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "id=" + userId + "&secret_key=" + secretKey
                })
                .then(response => response.text())
                .then(data => alert("User Password: " + data))
                .catch(error => console.error("Error:", error));
            }
        }
         document.addEventListener("DOMContentLoaded", function () {
            setTimeout(function () {
                let alertBox = document.getElementById("alertMessage");
                if (alertBox) {
                    alertBox.style.transition = "opacity 0.5s";
                    alertBox.style.opacity = "0";
                    setTimeout(() => alertBox.remove(), 500); 
                }
            }, 3000);
        });
    </script>
</head>
<body>
    <div class="vault-container">
    <?php if (isset($_SESSION["message"])): ?>
        <div id="alertMessage" class="alert alert-<?= $_SESSION["message_type"]; ?> text-center" role="alert">
            <?= $_SESSION["message"]; ?>
        </div>
        <?php unset($_SESSION["message"], $_SESSION["message_type"]); ?>
    <?php endif; ?>
        <h2 class="fw-bold text-dark">Admin Vault</h2>
        <table class="table table-bordered text-dark">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($stmt->fetch()): ?>
                    <tr>
                        <td><?= $id ?></td>
                        <td><?= htmlspecialchars($username) ?></td>
                        <td><?= htmlspecialchars($email) ?></td>
                        <td>
                        <button class="btn btn-common btn-delete" onclick="deleteUser(<?= $id ?>)">Delete</button>
<button class="btn btn-common btn-unlock" onclick="unlockPassword(<?= $id ?>)">Unlock</button>
<a href="reset_password.php?id=<?= $id ?>" class="btn btn-common btn-warning">Reset</a>

                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <a href="logout.php" class="btn btn-logout w-100 mt-3"><span>Logout</span></a>
    </div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
