<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'db.php';

$errors  = [];
$success = "";
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name']    = trim($_POST['name']    ?? '');
    $old['email']   = trim($_POST['email']   ?? '');
    $old['phone']   = trim($_POST['phone']   ?? '');
    $old['address'] = trim($_POST['address'] ?? '');
    $password       = $_POST['password']         ?? '';
    $confirm        = $_POST['confirm_password'] ?? '';

    // Server-side validation
    if (empty($old['name'])) {
        $errors[] = "Full name is required.";
    } elseif (strlen($old['name']) < 2) {
        $errors[] = "Name must be at least 2 characters.";
    }

    if (empty($old['email']) || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }

    if (!empty($old['phone']) && !preg_match('/^[0-9+\-\s]{7,15}$/', $old['phone'])) {
        $errors[] = "Please enter a valid phone number.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $old['email']);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "This email is already registered. <a href='login.php'>Login instead?</a>";
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $ins    = $conn->prepare("INSERT INTO users (name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)");
            $ins->bind_param("sssss", $old['name'], $old['email'], $hashed, $old['phone'], $old['address']);

            if ($ins->execute()) {
                $success = "Account created successfully! You can now <a href='login.php'>login here</a>.";
                $old     = []; // clear form on success
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
            $ins->close();
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
    <title>Register — ApexPlanet</title>
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
            max-width: 430px;
        }
        .logo { text-align: center; margin-bottom: 1.8rem; }
        .logo h1 { font-size: 1.7rem; color: #1a5c4f; letter-spacing: -0.5px; }
        .logo h1 span { color: #3aafa9; }
        .logo p { color: #999; font-size: 0.8rem; margin-top: 2px; }
        h2 { text-align: center; font-size: 1.2rem; color: #333; margin-bottom: 1.5rem; font-weight: 600; }

        .alert { padding: 0.85rem 1rem; border-radius: 8px; margin-bottom: 1.2rem; font-size: 0.9rem; }
        .alert-error { background: #fdecea; color: #c0392b; border-left: 4px solid #e74c3c; }
        .alert-error ul { padding-left: 1.2rem; }
        .alert-error li { margin-bottom: 3px; }
        .alert-success { background: #e8f8f5; color: #1a7a5e; border-left: 4px solid #3aafa9; }
        .alert-success a { color: #1a5c4f; font-weight: 600; }

        .form-group { margin-bottom: 1.1rem; }
        label { display: block; font-size: 0.85rem; font-weight: 600; color: #444; margin-bottom: 0.35rem; }
        .input-wrap { position: relative; }
        .input-wrap span {
            position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%);
            color: #aaa; font-size: 1rem; pointer-events: none;
        }
        input[type="text"], input[type="email"], input[type="password"] {
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
        .hint { font-size: 0.75rem; color: #999; margin-top: 3px; }

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
            letter-spacing: 0.3px;
        }
        button[type="submit"]:hover { background: #3aafa9; }
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
    <h2>Create an Account</h2>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success">✅ <?= $success ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php" id="regForm" novalidate>
        <div class="form-group">
            <label for="name">Full Name</label>
            <div class="input-wrap">
                <span>👤</span>
                <input type="text" id="name" name="name"
                       value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                       placeholder="e.g. Ravi Kumar"
                       minlength="2" required>
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <div class="input-wrap">
                <span>✉️</span>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                       placeholder="ravi@example.com" required>
            </div>
        </div>

        <div class="form-group">
            <label for="phone">Phone Number <span style="font-weight:400;color:#999;">(optional)</span></label>
            <div class="input-wrap">
                <span>📱</span>
                <input type="text" id="phone" name="phone"
                       value="<?= htmlspecialchars($old['phone'] ?? '') ?>"
                       placeholder="+91 98765 43210">
            </div>
        </div>

        <div class="form-group">
            <label for="address">Address <span style="font-weight:400;color:#999;">(optional)</span></label>
            <div class="input-wrap">
                <span>📍</span>
                <input type="text" id="address" name="address"
                       value="<?= htmlspecialchars($old['address'] ?? '') ?>"
                       placeholder="City, State">
            </div>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="input-wrap">
                <span>🔒</span>
                <input type="password" id="password" name="password"
                       placeholder="Min. 6 characters"
                       minlength="6" required>
            </div>
            <p class="hint">Use at least 6 characters.</p>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <div class="input-wrap">
                <span>🔒</span>
                <input type="password" id="confirm_password" name="confirm_password"
                       placeholder="Repeat your password" required>
            </div>
            <p class="hint" id="matchHint"></p>
        </div>

        <button type="submit">Register</button>
    </form>

    <hr class="divider">
    <p class="footer-link">Already have an account? <a href="login.php">Login</a></p>
</div>

<script>
// Client-side validation
const form      = document.getElementById('regForm');
const password  = document.getElementById('password');
const confirm   = document.getElementById('confirm_password');
const matchHint = document.getElementById('matchHint');

// Real-time password match feedback
confirm.addEventListener('input', () => {
    if (confirm.value === '') { matchHint.textContent = ''; return; }
    if (confirm.value === password.value) {
        matchHint.textContent = '✅ Passwords match';
        matchHint.style.color = '#1a7a5e';
        confirm.classList.remove('invalid');
    } else {
        matchHint.textContent = '❌ Passwords do not match';
        matchHint.style.color = '#e74c3c';
        confirm.classList.add('invalid');
    }
});

form.addEventListener('submit', function(e) {
    let valid = true;

    // Name check
    const name = document.getElementById('name');
    if (name.value.trim().length < 2) {
        name.classList.add('invalid');
        name.focus();
        valid = false;
    } else {
        name.classList.remove('invalid');
    }

    // Email check
    const email = document.getElementById('email');
    const emailRx = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRx.test(email.value.trim())) {
        email.classList.add('invalid');
        if (valid) email.focus();
        valid = false;
    } else {
        email.classList.remove('invalid');
    }

    // Password length
    if (password.value.length < 6) {
        password.classList.add('invalid');
        if (valid) password.focus();
        valid = false;
    } else {
        password.classList.remove('invalid');
    }

    // Password match
    if (confirm.value !== password.value) {
        confirm.classList.add('invalid');
        if (valid) confirm.focus();
        valid = false;
    }

    if (!valid) e.preventDefault();
});
</script>
</body>
</html>
