<?php
// Datenbankverbindung einbinden
require_once 'config.php';

// Nur Nickname und aktuellen Feuchtigkeitsstand aus der Tabelle 'plants' holen
$stmt = $pdo->query("SELECT nickname, current_moisture FROM plants ORDER BY id ASC");
$plants = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gnüüschanne</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-stone-50 text-stone-900 min-h-screen font-sans">

    <!-- Mobile Container -->
    <div class="max-w-md mx-auto min-h-screen flex flex-col">

        <!-- Schlichter Header -->
        <header class="bg-white border-b border-stone-200 px-5 py-4 sticky top-0">
            <h1 class="text-xl font-bold tracking-tight">Gnüüschanne</h1>
        </header>

        <!-- Pflanzen-Liste (untereinander) -->
        <main class="p-4 flex flex-col gap-3">
            <?php foreach ($plants as $plant): ?>
                <div class="bg-white border border-stone-200 rounded-xl p-4 flex justify-between items-center shadow-sm">
                    <span class="font-medium text-base">
                        <?= htmlspecialchars($plant['nickname']) ?>
                    </span>
                    <span class="font-bold text-stone-700 bg-stone-100 px-3 py-1 rounded-lg text-sm">
                        <?= (int)$plant['current_moisture'] ?>%
                    </span>
                </div>
            <?php endforeach; ?>
        </main>

    </div>

</body>
</html>