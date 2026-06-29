<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$deletedName = '';

if ($id > 0) {
    // Fetch the name first so we can show it in the success message
    $lookup = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $lookup->bind_param("i", $id);
    $lookup->execute();
    $lookup->bind_result($deletedName);
    $lookup->fetch();
    $lookup->close();

    // Delete using a prepared statement
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // If the deleted user was the logged-in user, log them out
    if ($id === (int)$_SESSION['user_id']) {
        $conn->close();
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit();
    }
}

$conn->close();
$nameParam = $deletedName ? '&name=' . urlencode($deletedName) : '';
header("Location: manage_users.php?deleted=1" . $nameParam);
exit();
?>