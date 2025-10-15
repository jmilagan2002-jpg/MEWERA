<?php
session_start();
include 'db.php';

if (isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm = trim($_POST['confirm']);

    if ($password !== $confirm) {
        echo "<script>alert('Passwords do not match!');</script>";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");

        if (!$stmt) {
            die("SQL error: " . $conn->error);
        }

        $stmt->bind_param("sss", $username, $email, $hashed);

        if ($stmt->execute()) {
            echo "<script>alert('Registration successful! You can now login.'); window.location='login.php';</script>";
        } else {
            echo "<script>alert('Username or email already exists.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Cap Store</title>
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
        .register-container {
            background: #fff;
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 370px;
            text-align: center;
            animation: fadeIn 0.8s ease;
        }
        .register-container img {
            width: 70px;
            margin-bottom: 10px;
        }
        .register-container h2 {
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

<div class="register-container">
    
    <h2>Create Account</h2>
    <form method="post">
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="confirm" placeholder="Confirm Password" required>
        <button type="submit" name="register">Register</button>
        <div class="bottom-text">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </form>
</div>

</body>
</html>
