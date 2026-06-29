<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'db.php';

$errors  = [];
$oldEmail = '';

// Show logout success message
$loggedOut = isset($_GET['logout']) && $_GET['logout'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldEmail = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Server-side validation
    if (empty($oldEmail) || !filter_var($oldEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $oldEmail);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id, $name, $hashed_password);
        $stmt->fetch();

        if ($stmt->num_rows === 1 && password_verify($password, $hashed_password)) {
            $_SESSION['user_id']    = $id;
            $_SESSION['user_name']  = $name;
            $_SESSION['user_email'] = $oldEmail;
            header("Location: dashboard.php?welcome=1");
            exit();
        } else {
            $errors[] = "Incorrect email or password. Please try again.";
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — ApexPlanet</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e8f5e9 0%, #e0f2f1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(26,92,79,0.12);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 400px;
        }
        .logo { text-align: center; margin-bottom: 1.8rem; }
        .logo h1 { font-size: 1.7rem; color: #1a5c4f; }
        .logo h1 span { color: #3aafa9; }
        .logo p { color: #999; font-size: 0.8rem; margin-top: 2px; }
        h2 { text-align: center; font-size: 1.2rem; color: #333; margin-bottom: 1.5rem; font-weight: 600; }

        .alert { padding: 0.85rem 1rem; border-radius: 8px; margin-bottom: 1.2rem; font-size: 0.9rem; }
        .alert-error   { background: #fdecea; color: #c0392b; border-left: 4px solid #e74c3c; }
        .alert-error ul { padding-left: 1.2rem; }
        .alert-success  { background: #e8f8f5; color: #1a7a5e; border-left: 4px solid #3aafa9; }

        .form-group { margin-bottom: 1.1rem; }
        label { display: block; font-size: 0.85rem; font-weight: 600; color: #444; margin-bottom: 0.35rem; }
        .input-wrap { position: relative; }
        .input-wrap span {
            position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%);
            color: #aaa; font-size: 1rem; pointer-events: none;
        }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 0.65rem 0.9rem 0.65rem 2.2rem;
            border: 1.5px solid #ddd;
            border-radius: 7px;
            font-size: 0.95rem;
            color: #333;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #fafafa;
        }
        input:focus {
            outline: none;
            border-color: #3aafa9;
            box-shadow: 0 0 0 3px rgba(58,175,169,0.15);
            background: #fff;
        }
        input.invalid { border-color: #e74c3c; }

        button[type="submit"] {
            width: 100%;
            padding: 0.8rem;
            background: #1a5c4f;
            color: #fff;
            border: none;
            border-radius: 7px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 0.4rem;
            transition: background 0.2s, transform 0.1s;
        }
        button[type="submit"]:hover  { background: #3aafa9; }
        button[type="submit"]:active { transform: scale(0.98); }

        .footer-link { text-align: center; margin-top: 1.2rem; font-size: 0.9rem; color: #777; }
        .footer-link a { color: #3aafa9; text-decoration: none; font-weight: 600; }
        .footer-link a:hover { text-decoration: underline; }
        .divider { border: none; border-top: 1px solid #eee; margin: 1.5rem 0; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">
        <h1>Apex<span>Planet</span></h1>
        <p>Software Pvt Ltd — Internship Portal</p>
    </div>
    <h2>Login to Your Account</h2>

    <?php if ($loggedOut): ?>
    <div class="alert alert-success">✅ You have been logged out successfully.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="POST" action="login.php" id="loginForm" novalidate>
        <div class="form-group">
            <label for="email">Email Address</label>
            <div class="input-wrap">
                <span>✉️</span>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($oldEmail) ?>"
                       placeholder="ravi@example.com" required autofocus>
            </div>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrap">
                <span>🔒</span>
                <input type="password" id="password" name="password"
                       placeholder="Your password" required>
            </div>
        </div>

        <button type="submit">Login</button>
    </form>

    <hr class="divider">
    <p class="footer-link">Don't have an account? <a href="register.php">Register</a></p>
</div>

<script>
const form = document.getElementById('loginForm');
form.addEventListener('submit', function(e) {
    let valid = true;

    const email = document.getElementById('email');
    const emailRx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRx.test(email.value.trim())) {
        email.classList.add('invalid');
        email.focus();
        valid = false;
    } else {
        email.classList.remove('invalid');
    }

    const password = document.getElementById('password');
    if (password.value.trim() === '') {
        password.classList.add('invalid');
        if (valid) password.focus();
        valid = false;
    } else {
        password.classList.remove('invalid');
    }

    if (!valid) e.preventDefault();
});
</script>
</body>
</html>


