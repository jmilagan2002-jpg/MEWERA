<?php
session_start();
include 'db.php';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT user_id, password, role FROM users WHERE username=?");
    if (!$stmt) {
        die("SQL error: " . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hashed, $role);
        $stmt->fetch();

        if (password_verify($password, $hashed)) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;

            if ($role === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            echo "<script>alert('Invalid password!');</script>";
        }
    } else {
        echo "<script>alert('No account found with that username.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Cap Store</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            height: 100vh;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #d7e1ec, #a3b8cc);
        }
        .login-container {
            background: #fff;
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 350px;
            text-align: center;
            animation: fadeIn 0.8s ease;
        }
        .login-container img {
            width: 70px;
            margin-bottom: 10px;
        }
        .login-container h2 {
            margin-bottom: 25px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 26px;
        }
        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
            transition: border 0.3s;
        }
        input:focus {
            border-color: #4b6cb7;
            outline: none;
        }
        button {
            width: 100%;
            padding: 12px;
            background: #4b6cb7;
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }
        button:hover {
            background: #395591;
            transform: translateY(-2px);
        }
        .bottom-text {
            margin-top: 15px;
            font-size: 14px;
            color: #555;
        }
        .bottom-text a {
            color: #4b6cb7;
            text-decoration: none;
            font-weight: 500;
        }
        .bottom-text a:hover {
            text-decoration: underline;
        }
        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(10px);}
            to {opacity: 1; transform: translateY(0);}
        }
    </style>
</head>
<body>

<div class="login-container">
   
    <h2>Log In Kap!</h2>
    <form method="post">
        <input type="text" name="username" placeholder="Enter Username" required>
        <input type="password" name="password" placeholder="Enter Password" required>
        <button type="submit" name="login">Login</button>
        <div class="bottom-text">
            Don't have an account? <a href="register.php">Register</a>
        </div>
    </form>
</div>

</body>
</html>
