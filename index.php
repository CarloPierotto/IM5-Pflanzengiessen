<?php
// Datenbankverbindung einbinden
require_once 'config.php';

$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;

// Pflanzen des eingeloggten Users holen
$plants = [];
if ($userId) {
    $stmt = $pdo->prepare("SELECT * FROM plants WHERE user_id = ? ORDER BY id ASC");
    $stmt->execute([$userId]);
    $plants = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gnüüschanne</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts Icons (Material Symbols) -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
    </style>
</head>
<body class="bg-[#f7f6f3] text-stone-900 min-h-screen antialiased selection:bg-emerald-100">

    <div class="max-w-md mx-auto min-h-screen flex flex-col pb-28">

        <!-- Header -->
        <header class="bg-white/80 backdrop-blur-md border-b border-stone-200/80 px-5 py-4 sticky top-0 z-20 flex justify-between items-center">
            <h1 class="text-xl font-semibold tracking-tight cursor-pointer text-stone-800" onclick="showList()">Gnüüschanne</h1>
            
            <button onclick="toggleAuthView()" class="flex items-center gap-2 text-xs font-medium text-stone-600 hover:text-stone-900 bg-stone-100 hover:bg-stone-200/80 px-3 py-1.5 rounded-full transition">
                <?php if ($userId): ?>
                    <span><?= htmlspecialchars($username) ?></span>
                <?php endif; ?>
                <span class="material-symbols-outlined text-[18px]">account_circle</span>
            </button>
        </header>

        <!-- HAUPTANSICHT: Pflanzen-Liste -->
        <main id="main-view" class="p-4 flex flex-col gap-3">
            <?php if (!$userId): ?>
                <div class="bg-white border border-stone-200/80 rounded-2xl p-6 text-center space-y-4 shadow-sm mt-6">
                    <span class="material-symbols-outlined text-4xl text-emerald-700">potted_plant</span>
                    <p class="text-sm text-stone-600">Bitte melde dich oben rechts an, um deine Pflanzen zu verwalten.</p>
                    <button onclick="toggleAuthView()" class="w-full bg-stone-900 hover:bg-stone-800 text-white text-sm font-medium py-2.5 rounded-xl transition shadow-sm">
                        Anmelden / Registrieren
                    </button>
                </div>
            <?php elseif (empty($plants)): ?>
                <div class="bg-white border border-stone-200/80 rounded-2xl p-8 text-center text-sm text-stone-500 shadow-sm mt-6 space-y-2">
                    <span class="material-symbols-outlined text-3xl text-stone-400">spa</span>
                    <p>Noch keine Pflanzen vorhanden.<br>Füge unten rechts deine erste Pflanze hinzu.</p>
                </div>
            <?php else: ?>
                <?php foreach ($plants as $plant): 
                    $min = max(0, min(100, (int)$plant['min_moisture']));
                    $max = max(0, min(100, (int)$plant['max_moisture']));
                    $cur = max(0, min(100, (int)$plant['current_moisture']));
                    $width = max(0, $max - $min);
                ?>
                    <div onclick="showDetail(<?= htmlspecialchars(json_encode($plant), ENT_QUOTES, 'UTF-8') ?>)" 
                         class="bg-white border border-stone-200/80 rounded-2xl p-4 flex flex-col gap-3 shadow-sm cursor-pointer hover:border-emerald-600/50 hover:shadow transition group">
                        
                        <div class="flex justify-between items-center">
                            <span class="font-medium text-base text-stone-800 group-hover:text-emerald-900 transition">
                                <?= htmlspecialchars($plant['nickname']) ?>
                            </span>
                            <span class="text-xs text-stone-400 font-mono">
                                <?= (int)$plant['current_moisture'] ?>% Ist
                            </span>
                        </div>

                        <!-- Feuchtigkeits-Balken -->
                        <div class="relative w-full h-2.5 bg-red-100 rounded-full overflow-visible">
                            <div class="absolute top-0 bottom-0 bg-emerald-500 rounded-full" 
                                 style="left: <?= $min ?>%; width: <?= $width ?>%;"></div>
                            <div class="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 w-3.5 h-3.5 bg-stone-900 border-2 border-white rounded-full shadow-sm" 
                                 style="left: <?= $cur ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>

        <!-- ANSICHT: LOGIN / KONTO -->
        <div id="auth-view" class="hidden p-4 flex flex-col gap-4">
            <button onclick="showList()" class="self-start text-xs font-medium text-stone-500 hover:text-stone-900 flex items-center gap-1 mb-1">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span> Zurück
            </button>

            <div class="bg-white border border-stone-200/80 rounded-2xl p-6 shadow-sm space-y-4">
                <?php if ($userId): ?>
                    <h2 class="text-lg font-semibold text-stone-800">Konto</h2>
                    <p class="text-sm text-stone-600">Angemeldet als <b class="text-stone-900"><?= htmlspecialchars($username) ?></b></p>
                    <button onclick="logoutUser()" class="w-full bg-stone-100 hover:bg-red-50 text-red-600 font-medium py-2.5 rounded-xl text-sm transition">
                        Abmelden
                    </button>
                <?php else: ?>
                    <h2 id="auth-title" class="text-lg font-semibold text-stone-800">Anmelden</h2>
                    <form onsubmit="submitAuth(event)" class="space-y-3">
                        <input type="hidden" id="auth-action" value="login">
                        <div>
                            <label class="block text-xs font-medium text-stone-500 mb-1">Benutzername</label>
                            <input type="text" id="auth-username" required class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:border-stone-900 transition">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-stone-500 mb-1">Passwort</label>
                            <input type="password" id="auth-password" required class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:border-stone-900 transition">
                        </div>
                        <p id="auth-error" class="hidden text-xs text-red-600 font-medium pt-1"></p>
                        <button type="submit" id="auth-submit-btn" class="w-full bg-stone-900 hover:bg-stone-800 text-white font-medium py-2.5 rounded-xl text-sm transition shadow-sm mt-2">
                            Anmelden
                        </button>
                    </form>
                    <div class="pt-3 border-t border-stone-100 text-center">
                        <button type="button" onclick="switchAuthMode()" id="auth-switch-btn" class="text-xs text-stone-500 hover:text-stone-900 transition">
                            Noch kein Konto? Registrieren
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ANSICHT: NEUE PFLANZE HINZUFÜGEN (Mit KI-Kamera-Erkennung) -->
        <div id="add-view" class="hidden p-4 flex flex-col gap-4">
            <button onclick="showList()" class="self-start text-xs font-medium text-stone-500 hover:text-stone-900 flex items-center gap-1 mb-1">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span> Abbrechen
            </button>

            <div class="bg-white border border-stone-200/80 rounded-2xl p-6 shadow-sm space-y-5">
                <h2 class="text-lg font-semibold text-stone-800">Neue Pflanze verbinden</h2>

                <!-- Option A: KI-Foto-Erkennung -->
                <div class="space-y-2">
                    <label class="block text-xs font-medium text-stone-500">Per Foto erkennen (KI)</label>
                    <input type="file" id="camera-input" accept="image/*" capture="environment" class="hidden" onchange="identifyPlantByPhoto(event)">
                    
                    <button type="button" onclick="document.getElementById('camera-input').click()" 
                            class="w-full bg-stone-50 hover:bg-stone-100 border border-dashed border-stone-300 rounded-2xl p-4 flex flex-col items-center justify-center gap-1.5 transition group">
                        <span class="material-symbols-outlined text-[26px] text-emerald-700 group-hover:scale-110 transition">photo_camera</span>
                        <span id="camera-status-text" class="text-xs font-medium text-stone-700">Foto aufnehmen oder auswählen</span>
                    </button>

                    <!-- Vorschau des aufgenommenen Fotos & KI-Ergebnis -->
                    <div id="ai-preview-box" class="hidden bg-stone-50 border border-stone-200/80 rounded-xl p-3 flex items-center gap-3">
                        <img id="ai-preview-img" src="" alt="Vorschau" class="w-12 h-12 rounded-lg object-cover bg-stone-200 shrink-0">
                        <div class="text-xs">
                            <span id="ai-result-title" class="font-semibold text-stone-800 block">Analysiere Bild...</span>
                            <span id="ai-result-sub" class="text-stone-500">Bitte kurz warten</span>
                        </div>
                    </div>
                </div>

                <!-- Option B: Manuelle Suche in Plantbook -->
                <div class="space-y-2 pt-2 border-t border-stone-100">
                    <label class="block text-xs font-medium text-stone-500">Oder manuell suchen (Plantbook)</label>
                    <div class="flex gap-2">
                        <input type="text" id="plant-search-input" placeholder="z.B. monstera, ficus..." 
                               onkeydown="if(event.key==='Enter'){event.preventDefault(); searchPlants();}"
                               class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:border-stone-900 transition">
                        <button type="button" onclick="searchPlants()" class="bg-stone-900 hover:bg-stone-800 text-white px-4 py-2.5 rounded-xl text-sm font-medium transition shrink-0">
                            Suchen
                        </button>
                    </div>
                    <div id="search-results" class="hidden max-h-48 overflow-y-auto border border-stone-200 rounded-xl divide-y divide-stone-100 bg-stone-50"></div>
                </div>

                <!-- Speicher-Formular -->
                <form id="add-plant-form" onsubmit="createNewPlant(event)" class="space-y-4 pt-3 border-t border-stone-100">
                    <div>
                        <label class="block text-xs font-medium text-stone-400 mb-1">Ausgewählte Art (PID)</label>
                        <input type="text" id="new-pid" required readonly placeholder="Wird durch Foto oder Suche ausgefüllt..." 
                               class="w-full bg-stone-100 text-stone-500 border border-stone-200 rounded-xl px-3.5 py-2 text-xs italic focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-stone-500 mb-1">Dein Spitzname</label>
                        <input type="text" id="new-nickname" required placeholder="z.B. Monstera Wohnzimmer" 
                               class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:border-stone-900 transition">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-stone-500 mb-1">ESP32 Geräte-ID</label>
                        <input type="text" id="new-esp" required placeholder="z.B. ESP32-Node-01" 
                               class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3.5 py-2.5 text-sm font-mono focus:outline-none focus:border-stone-900 transition">
                    </div>

                    <p id="add-error" class="hidden text-xs text-red-600 font-medium"></p>

                    <button type="submit" id="add-submit-btn" class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-medium py-3 rounded-xl text-sm transition shadow-sm mt-2">
                        Pflanze hinzufügen
                    </button>
                </form>
            </div>
        </div>

        <!-- DETAILANSICHT -->
        <div id="detail-view" class="hidden p-4 flex flex-col gap-4">
            <button onclick="showList()" class="self-start text-xs font-medium text-stone-500 hover:text-stone-900 flex items-center gap-1 mb-1">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span> Übersicht
            </button>

            <div class="bg-white border border-stone-200/80 rounded-2xl p-6 shadow-sm space-y-6">
                <div>
                    <h2 id="det-nickname" class="text-xl font-semibold text-stone-900"></h2>
                    <p id="det-pid" class="text-xs text-stone-400 italic mt-0.5"></p>
                </div>

                <!-- Feuchtigkeits-Balken -->
                <div class="space-y-2 bg-stone-50 border border-stone-100 rounded-2xl p-4">
                    <div class="flex justify-between items-center text-xs font-medium text-stone-500 mb-1">
                        <span>Bodenfeuchtigkeit</span>
                        <span id="det-moisture-val" class="font-bold text-stone-900"></span>
                    </div>
                    <div class="relative w-full h-3 bg-red-100 rounded-full overflow-visible">
                        <div id="det-bar-ideal" class="absolute top-0 bottom-0 bg-emerald-500 rounded-full transition-all duration-300"></div>
                        <div id="det-bar-marker" class="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 w-4 h-4 bg-stone-900 border-2 border-white rounded-full shadow transition-all duration-300"></div>
                    </div>
                </div>

                <!-- Ideale Bodenfeuchte mit Stift-Icon zum Bearbeiten -->
                <div class="pt-2">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="text-xs font-medium text-stone-400 block uppercase tracking-wider">Idealer Bereich</span>
                            <span id="det-ideal-text" class="text-sm font-semibold text-stone-800"></span>
                        </div>
                        <button type="button" onclick="toggleEditMoisture()" class="w-9 h-9 flex items-center justify-center text-stone-500 hover:text-stone-900 hover:bg-stone-100 rounded-xl transition" title="Idealbereich bearbeiten">
                            <span class="material-symbols-outlined text-[20px]">edit</span>
                        </button>
                    </div>

                    <!-- Verstecktes Bearbeitungsformular -->
                    <form id="edit-form" onsubmit="saveMoisture(event)" class="hidden space-y-3 mt-4 pt-4 border-t border-stone-100">
                        <input type="hidden" id="det-id">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-stone-400 mb-1">Min (%)</label>
                                <input type="number" id="det-min" min="0" max="100" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-stone-900">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-stone-400 mb-1">Max (%)</label>
                                <input type="number" id="det-max" min="0" max="100" class="w-full bg-stone-50 border border-stone-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-stone-900">
                            </div>
                        </div>

                        <button type="submit" id="save-btn" class="w-full bg-stone-900 hover:bg-stone-800 text-white font-medium py-2.5 rounded-xl text-xs transition shadow-sm">
                            Änderungen speichern
                        </button>
                    </form>
                </div>

                <!-- API Ladeindikator -->
                <div id="api-loading" class="text-center py-6 text-stone-400 text-xs border-t border-stone-100">
                    Lade Pflegehinweise...
                </div>

                <!-- API Inhalte -->
                <div id="api-content" class="hidden space-y-4 pt-4 border-t border-stone-100 text-sm">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-stone-50 border border-stone-100 rounded-xl p-3">
                            <div class="flex items-center gap-1.5 text-xs font-medium text-stone-400 mb-1">
                                <span class="material-symbols-outlined text-[16px]">thermostat</span> Temperatur
                            </div>
                            <span id="det-temp" class="text-xs font-semibold text-stone-800"></span>
                        </div>
                        <div class="bg-stone-50 border border-stone-100 rounded-xl p-3">
                            <div class="flex items-center gap-1.5 text-xs font-medium text-stone-400 mb-1">
                                <span class="material-symbols-outlined text-[16px]">humidity_percentage</span> Luftfeuchte
                            </div>
                            <span id="det-humid" class="text-xs font-semibold text-stone-800"></span>
                        </div>
                    </div>

                    <div class="space-y-3 pt-2">
                        <div class="text-xs font-medium text-stone-400 uppercase tracking-wider">Pflegehinweise</div>
                        
                        <div class="space-y-1">
                            <div class="flex items-center gap-1.5 text-xs font-medium text-stone-600">
                                <span class="material-symbols-outlined text-[16px] text-amber-600">light_mode</span> Lichtbedarf
                            </div>
                            <p id="det-light" class="text-xs text-stone-600 leading-relaxed pl-6"></p>
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-center gap-1.5 text-xs font-medium text-stone-600">
                                <span class="material-symbols-outlined text-[16px] text-blue-600">science</span> Düngung
                            </div>
                            <p id="det-fertilization" class="text-xs text-stone-600 leading-relaxed pl-6"></p>
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-center gap-1.5 text-xs font-medium text-stone-600">
                                <span class="material-symbols-outlined text-[16px] text-emerald-600">content_cut</span> Rückschnitt
                            </div>
                            <p id="det-pruning" class="text-xs text-stone-600 leading-relaxed pl-6"></p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- SCHWEBENDER + BUTTON UNTEN RECHTS -->
    <?php if ($userId): ?>
        <button id="fab-add-btn" onclick="showAddView()" 
                class="fixed bottom-6 right-6 z-30 bg-stone-900 hover:bg-stone-800 text-white w-14 h-14 rounded-full flex items-center justify-center shadow-xl transition transform hover:scale-105" 
                title="Pflanze hinzufügen">
            <span class="material-symbols-outlined text-[28px]">add</span>
        </button>
    <?php endif; ?>

    <script src="app.js"></script>
</body>
</html>