<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Nicht angemeldet.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = trim($_POST['nickname'] ?? '');
    $pid = trim($_POST['plantbook_pid'] ?? '');
    $esp_id = trim($_POST['esp_device_id'] ?? '');
    $min = isset($_POST['min_moisture']) ? (int)$_POST['min_moisture'] : 20;
    $max = isset($_POST['max_moisture']) ? (int)$_POST['max_moisture'] : 60;

    if ($nickname !== '' && $pid !== '' && $esp_id !== '') {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO plants (user_id, nickname, plantbook_pid, esp_device_id, current_moisture, min_moisture, max_moisture, auto_watering, manual_water_trigger)
                VALUES (?, ?, ?, ?, 0, ?, ?, 1, 0)
            ");
            $stmt->execute([$_SESSION['user_id'], $nickname, $pid, $esp_id, $min, $max]);
            echo json_encode(['success' => true]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'ESP-Geräte-ID existiert bereits oder Datenbankfehler.']);
            exit;
        }
    }
}
echo json_encode(['success' => false, 'error' => 'Bitte alle Felder ausfüllen.']);