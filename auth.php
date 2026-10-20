<?php
require_once 'config.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    echo json_encode(['success' => false, 'error' => 'Bitte Benutzername und Passwort eingeben.']);
    exit;
}

if ($action === 'register') {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Dieser Benutzername ist bereits vergeben.']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
    $stmt->execute([$username, $hash]);

    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['username'] = $username;
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'login') {
    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Benutzername oder Passwort falsch.']);
    exit;
}