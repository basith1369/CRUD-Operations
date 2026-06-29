<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

$errors = [];
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);

if ($id <= 0) {
    header("Location: manage_users.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $address = trim($_POST['address'] ?? '');

    // Validation
    if (empty($name) || strlen($name) < 2) {
        $errors[] = "Name must be at least 2 characters.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    if (!empty($phone) && !preg_match('/^[0-9+\-\s]{7,15}$/', $phone)) {
        $errors[] = "Please enter a valid phone number.";
    }

    if (empty($errors)) {
        // Ensure email isn't taken by a different user
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->bind_param("si", $email, $id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $errors[] = "This email is already used by another user.";
        } else {
            $upd = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
            $upd->bind_param("ssssi", $name, $email, $phone, $address, $id);

            if ($upd->execute()) {
                header("Location: manage_users.php?updated=1");
                exit();
            } else {
                $errors[] = "Update failed. Please try again.";
            }
            $upd->close();
        }
        $check->close();
    }
}

// Fetch current user data (pre-fill the form)
$stmt = $conn->prepare("SELECT id, name, email, phone, address FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res  = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();
$conn->close();

if (!$user) {
    header("Location: manage_users.php");
    exit();
}

// Use POST values if validation failed, otherwise DB values
$name    = $_POST['name']    ?? $user['name'];
$email   = $_POST['email']   ?? $user['email'];
$phone   = $_POST['phone']   ?? $user['phone'];
$address = $_POST['address'] ?? $user['address'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User — ApexPlanet</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f4f3; min-height: 100vh; }

        header {
            background: #1a5c4f; color: #fff; padding: 0.9rem 2rem;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        header h1 { font-size: 1.4rem; }
        header h1 span { color: #3aafa9; }
        .btn-logout {
            background: #3aafa9; color: #fff; border: none; padding: 0.45rem 1rem;
            border-radius: 6px; cursor: pointer; font-size: 0.9rem; text-decoration: none; font-weight: 600;
        }
        .btn-logout:hover { background: #fff; color: #1a5c4f; }

        .container { max-width: 560px; margin: 2.5rem auto; padding: 0 1rem; }
        .card { background: #fff; border-radius: 12px; padding: 2rem 2.2rem; box-shadow: 0 4px 20px rgba(0,0,0,0.07); }

        .back-link { display: inline-block; margin-bottom: 1rem; color: #3aafa9; text-decoration: none; font-size: 0.9rem; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }

        h2 { color: #1a5c4f; font-size: 1.4rem; margin-bottom: 0.3rem; }
        .sub { color: #888; font-size: 0.88rem; margin-bottom: 1.5rem; }

        .alert-error { background: #fdecea; color: #c0392b; border-left: 4px solid #e74c3c; padding: 0.85rem 1rem; border-radius: 8px; margin-bottom: 1.2rem; font-size: 0.9rem; }
        .alert-error ul { padding-left: 1.2rem; }

        .form-group { margin-bottom: 1.1rem; }
        label { display: block; font-size: 0.85rem; font-weight: 600; color: #444; margin-bottom: 0.35rem; }
        input[type="text"], input[type="email"] {
            width: 100%; padding: 0.65rem 0.9rem; border: 1.5px solid #ddd; border-radius: 7px;
            font-size: 0.95rem; color: #333; background: #fafafa; transition: border-color 0.2s, box-shadow 0.2s;
        }
        input:focus { outline: none; border-color: #3aafa9; box-shadow: 0 0 0 3px rgba(58,175,169,0.15); background: #fff; }

        .btn-row { display: flex; gap: 0.8rem; margin-top: 1.5rem; }
        button[type="submit"] {
            flex: 1; padding: 0.8rem; background: #1a5c4f; color: #fff; border: none;
            border-radius: 7px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s;
        }
        button[type="submit"]:hover { background: #3aafa9; }
        .btn-cancel {
            flex: 1; padding: 0.8rem; background: #f1f1f1; color: #555; border: none;
            border-radius: 7px; font-size: 1rem; font-weight: 600; cursor: pointer;
            text-align: center; text-decoration: none; transition: background 0.2s;
        }
        .btn-cancel:hover { background: #e5e5e5; }
    </style>
</head>
<body>

<header>
    <h1>Apex<span>Planet</span></h1>
    <a href="logout.php" class="btn-logout">Logout</a>
</header>

<div class="container">
    <a href="manage_users.php" class="back-link">← Back to Manage Users</a>
    <div class="card">
        <h2>Edit User</h2>
        <p class="sub">Updating record for User ID #<?= (int)$user['id'] ?></p>

        <?php if (!empty($errors)): ?>
        <div class="alert-error">
            <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>

        <form method="POST" action="edit_user.php?id=<?= (int)$user['id'] ?>">
            <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required minlength="2">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($phone ?? '') ?>" placeholder="+91 98765 43210">
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <input type="text" id="address" name="address" value="<?= htmlspecialchars($address ?? '') ?>" placeholder="City, State">
            </div>

            <div class="btn-row">
                <a href="manage_users.php" class="btn-cancel">Cancel</a>
                <button type="submit">Save Changes</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
