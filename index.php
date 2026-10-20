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

        <!-- Header mit + Button -->
        <header class="bg-white border-b border-stone-200 px-5 py-4 sticky top-0 z-10 flex justify-between items-center">
            <h1 class="text-xl font-bold tracking-tight cursor-pointer" onclick="showList()">Gnüüschanne</h1>
            <button onclick="showAddView()" class="bg-emerald-700 hover:bg-emerald-800 text-white w-8 h-8 rounded-lg font-bold text-lg flex items-center justify-center transition shadow-sm" title="Pflanze hinzufügen">
                +
            </button>
        </header>

        <!-- HAUPTANSICHT: Pflanzen-Liste mit Farbbalken -->
        <main id="main-view" class="p-4 flex flex-col gap-3">
            <?php foreach ($plants as $plant): 
                $min = max(0, min(100, (int)$plant['min_moisture']));
                $max = max(0, min(100, (int)$plant['max_moisture']));
                $cur = max(0, min(100, (int)$plant['current_moisture']));
                $width = max(0, $max - $min);
            ?>
                <div onclick="showDetail(<?= htmlspecialchars(json_encode($plant), ENT_QUOTES, 'UTF-8') ?>)" 
                     class="bg-white border border-stone-200 rounded-xl p-4 flex flex-col gap-3 shadow-sm cursor-pointer hover:border-emerald-500 transition">
                    
                    <span class="font-medium text-base">
                        <?= htmlspecialchars($plant['nickname']) ?>
                    </span>

                    <!-- Feuchtigkeits-Balken (Rot / Grün / Rot + aktueller Indikator) -->
                    <div class="relative w-full h-3 bg-red-200 rounded-full overflow-visible">
                        <div class="absolute top-0 bottom-0 bg-emerald-400 rounded-full" 
                             style="left: <?= $min ?>%; width: <?= $width ?>%;"></div>
                        <div class="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 w-4 h-4 bg-stone-900 border-2 border-white rounded-full shadow" 
                             style="left: <?= $cur ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </main>

        <!-- ANSICHT: NEUE PFLANZE HINZUFÜGEN -->
        <div id="add-view" class="hidden p-4 flex flex-col gap-4">
            <button onclick="showList()" class="self-start text-sm text-emerald-700 hover:text-emerald-900 font-medium mb-1">
                ← Abbrechen
            </button>

            <div class="bg-white border border-stone-200 rounded-xl p-5 shadow-sm space-y-4">
                <h2 class="text-lg font-bold">Neue Pflanze hinzufügen</h2>

                <!-- Schritt 1: Pflanze in Plantbook API suchen -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold uppercase text-stone-500">1. Pflanzenart suchen (Plantbook API)</label>
                    <div class="flex gap-2">
                        <input type="text" id="plant-search-input" placeholder="z.B. monstera, ficus, aloe..." 
                               onkeydown="if(event.key==='Enter'){event.preventDefault(); searchPlants();}"
                               class="w-full bg-stone-50 border border-stone-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500">
                        <button type="button" onclick="searchPlants()" class="bg-stone-800 hover:bg-stone-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shrink-0">
                            Suchen
                        </button>
                    </div>
                    <!-- Suchergebnisse -->
                    <div id="search-results" class="hidden max-h-44 overflow-y-auto border border-stone-200 rounded-lg divide-y divide-stone-100 bg-stone-50"></div>
                </div>

                <!-- Schritt 2: Formular zum Speichern -->
                <form id="add-plant-form" onsubmit="createNewPlant(event)" class="space-y-3 pt-3 border-t border-stone-100">
                    <div>
                        <label class="block text-xs text-stone-400 mb-1">Ausgewählte Art (PID)</label>
                        <input type="text" id="new-pid" required readonly placeholder="Bitte oben suchen & anklicken..." 
                               class="w-full bg-stone-100 text-stone-600 border border-stone-200 rounded-lg px-3 py-1.5 text-sm italic focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-stone-400 mb-1">Min. Feuchte (%)</label>
                            <input type="number" id="new-min" required min="0" max="100" value="20"
                                   class="w-full bg-stone-50 border border-stone-200 rounded-lg px-3 py-1.5 text-sm font-medium focus:outline-none focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs text-stone-400 mb-1">Max. Feuchte (%)</label>
                            <input type="number" id="new-max" required min="0" max="100" value="60"
                                   class="w-full bg-stone-50 border border-stone-200 rounded-lg px-3 py-1.5 text-sm font-medium focus:outline-none focus:border-emerald-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs text-stone-400 mb-1">Dein Spitzname</label>
                        <input type="text" id="new-nickname" required placeholder="z.B. Palme Balkon" 
                               class="w-full bg-stone-50 border border-stone-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs text-stone-400 mb-1">ESP32 Geräte-ID</label>
                        <input type="text" id="new-esp" required placeholder="z.B. ESP32-Node-04" 
                               class="w-full bg-stone-50 border border-stone-200 rounded-lg px-3 py-1.5 text-sm font-mono focus:outline-none focus:border-emerald-500">
                    </div>

                    <p id="add-error" class="hidden text-xs text-red-600 font-medium"></p>

                    <button type="submit" id="add-submit-btn" class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-medium py-2.5 rounded-lg text-sm transition mt-2">
                        Pflanze speichern
                    </button>
                </form>
            </div>
        </div>

        <!-- DETAILANSICHT -->
        <div id="detail-view" class="hidden p-4 flex flex-col gap-4">
            <button onclick="showList()" class="self-start text-sm text-emerald-700 hover:text-emerald-900 font-medium mb-1">
                ← Zurück zur Übersicht
            </button>

            <div class="bg-white border border-stone-200 rounded-xl p-5 shadow-sm space-y-5">
                <div>
                    <h2 id="det-nickname" class="text-xl font-bold"></h2>
                    <p id="det-pid" class="text-xs italic text-stone-400"></p>
                </div>

                <!-- Feuchtigkeits-Balken in der Detailansicht -->
                <div class="space-y-2">
                    <div class="relative w-full h-3.5 bg-red-200 rounded-full overflow-visible">
                        <div id="det-bar-ideal" class="absolute top-0 bottom-0 bg-emerald-400 rounded-full transition-all duration-300"></div>
                        <div id="det-bar-marker" class="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 w-4 h-4 bg-stone-900 border-2 border-white rounded-full shadow transition-all duration-300"></div>
                    </div>
                </div>

                <!-- Ideale Bodenfeuchte: Anzeige + Stift-Icon zum Bearbeiten -->
                <div class="pt-3 border-t border-stone-100">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="text-xs font-semibold uppercase text-stone-500 block">Ideale Bodenfeuchte</span>
                            <span id="det-ideal-text" class="text-sm font-medium text-stone-800"></span>
                        </div>
                        <button type="button" onclick="toggleEditMoisture()" class="p-2 text-stone-400 hover:text-stone-700 hover:bg-stone-100 rounded-lg transition" title="Idealbereich bearbeiten">
                            ✏️
                        </button>
                    </div>

                    <!-- Verstecktes Formular -->
                    <form id="edit-form" onsubmit="saveMoisture(event)" class="hidden space-y-3 mt-3 pt-3 border-t border-stone-100">
                        <input type="hidden" id="det-id">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs text-stone-400 mb-1">Minimum (%)</label>
                                <input type="number" id="det-min" min="0" max="100" class="w-full bg-stone-50 border border-stone-200 rounded-lg px-3 py-1.5 text-sm font-medium focus:outline-none focus:border-emerald-500">
                            </div>
                            <div>
                                <label class="block text-xs text-stone-400 mb-1">Maximum (%)</label>
                                <input type="number" id="det-max" min="0" max="100" class="w-full bg-stone-50 border border-stone-200 rounded-lg px-3 py-1.5 text-sm font-medium focus:outline-none focus:border-emerald-500">
                            </div>
                        </div>

                        <button type="submit" id="save-btn" class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-medium py-2 rounded-lg text-sm transition">
                            Speichern
                        </button>
                    </form>
                </div>

                <!-- Lade-Indikator -->
                <div id="api-loading" class="text-center py-4 text-stone-400 text-xs border-t border-stone-100">
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
                            <span class="text-xs text-stone-500 block font-medium">☀️ Licht:</span>
                            <p id="det-light" class="text-stone-800 text-xs mt-0.5 leading-relaxed"></p>
                        </div>
                        <div>
                            <span class="text-xs text-stone-500 block font-medium">⚗️ Düngung:</span>
                            <p id="det-fertilization" class="text-stone-800 text-xs mt-0.5 leading-relaxed"></p>
                        </div>
                        <div>
                            <span class="text-xs text-stone-500 block font-medium">✂️ Rückschnitt:</span>
                            <p id="det-pruning" class="text-stone-800 text-xs mt-0.5 leading-relaxed"></p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <script src="app.js"></script>
</body>
</html>