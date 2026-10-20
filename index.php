<?php
require_once 'config.php';

// Alle Pflanzen aus der Datenbank holen
$stmt = $pdo->query("SELECT * FROM plants ORDER BY id ASC");
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

    <div class="max-w-md mx-auto min-h-screen flex flex-col">

        <!-- Header -->
        <header class="bg-white border-b border-stone-200 px-5 py-4 sticky top-0 z-10">
            <h1 class="text-xl font-bold tracking-tight">Gnüüschanne</h1>
        </header>

        <!-- HAUPTANSICHT: Pflanzen-Liste -->
        <main id="main-view" class="p-4 flex flex-col gap-3">
            <?php foreach ($plants as $plant): ?>
                <div onclick="showDetail(<?= htmlspecialchars(json_encode($plant), ENT_QUOTES, 'UTF-8') ?>)" 
                     class="bg-white border border-stone-200 rounded-xl p-4 flex justify-between items-center shadow-sm cursor-pointer hover:border-emerald-500 transition">
                    <span class="font-medium text-base">
                        <?= htmlspecialchars($plant['nickname']) ?>
                    </span>
                    <span class="font-bold text-stone-700 bg-stone-100 px-3 py-1 rounded-lg text-sm">
                        <?= (int)$plant['current_moisture'] ?>%
                    </span>
                </div>
            <?php endforeach; ?>
        </main>

        <!-- DETAILANSICHT -->
        <div id="detail-view" class="hidden p-4 flex flex-col gap-4">
            <button onclick="showList()" class="self-start text-sm text-emerald-700 hover:text-emerald-900 font-medium mb-1">
                ← Zurück zur Übersicht
            </button>

            <div class="bg-white border border-stone-200 rounded-xl p-5 shadow-sm space-y-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 id="det-nickname" class="text-xl font-bold"></h2>
                        <p id="det-pid" class="text-xs italic text-stone-400"></p>
                    </div>
                    <span id="det-moisture-badge" class="font-bold px-2.5 py-1 rounded-lg text-sm"></span>
                </div>

                <!-- Formular: Ideale Bodenfeuchte (aus Datenbank) -->
                <form id="edit-form" onsubmit="saveMoisture(event)" class="space-y-3 pt-3 border-t border-stone-100">
                    <input type="hidden" id="det-id">
                    <div class="text-xs font-semibold uppercase text-stone-500">Ideale Bodenfeuchte (%)</div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-stone-400 mb-1">Minimum</label>
                            <input type="number" id="det-min" class="w-full bg-stone-50 border border-stone-200 rounded-lg px-3 py-1.5 text-sm font-medium focus:outline-none focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs text-stone-400 mb-1">Maximum</label>
                            <input type="number" id="det-max" class="w-full bg-stone-50 border border-stone-200 rounded-lg px-3 py-1.5 text-sm font-medium focus:outline-none focus:border-emerald-500">
                        </div>
                    </div>

                    <button type="submit" id="save-btn" class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-medium py-2 rounded-lg text-sm transition mt-2">
                        Änderungen speichern
                    </button>
                </form>

                <!-- Lade-Indikator -->
                <div id="api-loading" class="text-center py-4 text-stone-400 text-xs">
                    Lade und übersetze Pflegehinweise...
                </div>

                <!-- API-Inhalte (Klima & übersetzte Care Information) -->
                <div id="api-content" class="hidden space-y-3 pt-3 border-t border-stone-100 text-sm">
                    
                    <div class="space-y-2">
                        <div class="text-xs font-semibold uppercase text-stone-500">Klima-Empfehlung</div>
                        <div class="flex justify-between text-xs">
                            <span class="text-stone-500">🌡️ Umgebungstemperatur:</span>
                            <span id="det-temp" class="font-medium"></span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-stone-500">💨 Luftfeuchtigkeit:</span>
                            <span id="det-humid" class="font-medium"></span>
                        </div>
                    </div>

                    <div class="space-y-2 pt-2 border-t border-stone-100">
                        <div class="text-xs font-semibold uppercase text-stone-500">Pflegehinweise</div>
                        <div>
                            <span class="text-xs text-stone-500 block font-medium">☀️ Licht (Light):</span>
                            <p id="det-light" class="text-stone-800 text-xs mt-0.5 leading-relaxed"></p>
                        </div>
                        <div>
                            <span class="text-xs text-stone-500 block font-medium">⚗️ Düngung (Fertilization):</span>
                            <p id="det-fertilization" class="text-stone-800 text-xs mt-0.5 leading-relaxed"></p>
                        </div>
                        <div>
                            <span class="text-xs text-stone-500 block font-medium">✂️ Rückschnitt (Pruning):</span>
                            <p id="det-pruning" class="text-stone-800 text-xs mt-0.5 leading-relaxed"></p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <!-- Ausgelagertes JavaScript -->
    <script src="app.js"></script>
</body>
</html>