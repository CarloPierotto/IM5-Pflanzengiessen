<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Nicht angemeldet.']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Kein gültiges Bild empfangen.']);
    exit;
}

if (empty($plantnet_api_key) || $plantnet_api_key === 'DEIN_PLANTNET_API_KEY_HIER_EINTRAGEN') {
    echo json_encode(['success' => false, 'error' => 'Bitte trage zuerst deinen Pl@ntNet API-Key in der config.php ein.']);
    exit;
}

$tmpPath = $_FILES['image']['tmp_name'];
$mimeType = $_FILES['image']['type'] ?: 'image/jpeg';
$fileName = $_FILES['image']['name'] ?: 'plant.jpg';

// Anfrage an die kostenlose Pl@ntNet API (project=all erkennt Zimmer- & Wildpflanzen weltweit)
$url = "https://my-api.plantnet.org/v2/identify/all?api-key=" . urlencode($plantnet_api_key) . "&lang=de";

$cfile = new CURLFile($tmpPath, $mimeType, $fileName);
$postData = [
    'images' => $cfile,
    'organs' => 'auto'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$response) {
    echo json_encode(['success' => false, 'error' => 'Pflanze konnte auf dem Foto nicht erkannt werden.']);
    exit;
}

$data = json_decode($response, true);

if (!empty($data['results'][0])) {
    $bestMatch = $data['results'][0];
    // Botanischer Name ohne Autorenkürzel (z.B. "Monstera deliciosa")
    $scientificName = $bestMatch['species']['scientificNameWithoutAuthor'] ?? '';
    // Deutscher Name (falls vorhanden)
    $commonName = $bestMatch['species']['commonNames'][0] ?? $scientificName;
    // Wahrscheinlichkeit in Prozent
    $score = round(($bestMatch['score'] ?? 0) * 100);

    echo json_encode([
        'success' => true,
        'scientific_name' => strtolower($scientificName),
        'display_name' => $commonName,
        'score' => $score
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Keine passende Pflanze gefunden.']);