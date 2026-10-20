<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $min = $_POST['min_moisture'] ?? null;
    $max = $_POST['max_moisture'] ?? null;

    if ($id !== null && $min !== null && $max !== null) {
        $stmt = $pdo->prepare("UPDATE plants SET min_moisture = ?, max_moisture = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([(int)$min, (int)$max, (int)$id, $_SESSION['user_id']]);
        echo json_encode(['success' => true]);
        exit;
    }
}
echo json_encode(['success' => false]);