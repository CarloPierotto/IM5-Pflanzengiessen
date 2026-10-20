<?php
require_once 'config.php';

$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;

// Nur die Pflanzen des aktuell eingeloggten Benutzers laden
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
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-stone-50 text-stone-900 min-h-screen font-sans">

    <div class="max-w-md mx-auto min-h-screen flex flex-col pb-24">

        <!-- Header mit Account-Icon oben rechts -->
        <header class="bg-white border-b border-stone-200 px-5 py-4 sticky top-0 z-20 flex justify-between items-center">
            <h1 class="text-xl font-bold tracking-tight cursor-pointer" onclick="showList()">Gnüüschanne</h1>
            
            <button onclick="toggleAuthView()" class="flex items-center gap-2 text-sm font-medium text-stone-700 hover:text-emerald-700 bg-stone-100 hover:bg-stone-200 px-3 py-1.5 rounded-full transition" title="Konto / Anmelden">
                <?php if ($userId): ?>
                    <span class="text-xs font-semibold"><?= htmlspecialchars($username) ?></span>
                <?php endif; ?>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </button>
        </header>

        <!-- HAUPTANSICHT: Pflanzen-Liste -->
        <main id="main-view" class="p-4 flex flex-col gap-3">
            <?php if (!$userId): ?>
                <div class="bg-white border border-stone-200 rounded-xl p-6 text-center space-y-3 shadow-sm mt-4">
                    <p class="text-sm text-stone-600">Bitte melde dich oben rechts an, um deine Pflanzen zu sehen oder neue hinzuzufügen.</p>
                    <button onclick="toggleAuthView()" class="bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                        Jetzt anmelden
                    </button>
                </div>
            <?php elseif (empty($plants)): ?>
                <div class="bg-white border border-stone-200 rounded-xl p-6 text-center text-sm text-stone-500 shadow-sm mt-4">
                    Du hast noch keine Pflanzen hinzugefügt. Klicke unten rechts auf das <b>+</b> Symbol!
                </div>
            <?php else: ?>
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

                        <!-- Feuchtigkeits-Balken -->
                        <div class="relative w-full h-3 bg-red-200 rounded-full overflow-visible">
                            <div class="absolute top-0 bottom-0 bg-emerald-400 rounded-full" 
                                 style="left: <?= $min ?>%; width: <?= $width ?>%;"></div>
                            <div class="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 w-4 h-4 bg-stone-900 border-2 border-white rounded-full shadow" 
                                 style="left: <?= $cur ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>

        <!-- ANSICHT: LOGIN / REGISTRIERUNG / PROFIL -->
        <div id="auth-view" class="hidden p-4 flex flex-col gap-4">
            <button onclick="showList()" class="self-start text-sm text-emerald-700 hover:text-emerald-900 font-medium mb-1">
                ← Zurück
            </button>

            <div class="bg-white border border-stone-200 rounded-xl p-5 shadow-sm space-y-4">
                <?php if ($userId): ?>
                    <h2 class="text-lg font-bold">Dein Konto</h2>
                    <p class="text-sm text-stone-600">Angemeldet als <b><?= htmlspecialchars($username) ?></b></p>
                    <button onclick="logoutUser()" class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 rounded-lg text-sm transition">
                        Abmelden
                    </button>
                <?php else: ?>
                    <h2 id="auth-title" class="text-lg font-bold">Anmelden</h2>
                    <form onsubmit="submitAuth(event)" class="space-y-3">
                        <input type="hidden" id="auth-action" value="login">
                        <div>
                            <label class="block text-xs text-stone-400 mb-1">Benutzername</label>
                            <input type="text" id="auth-username" required class="w-full bg-stone-50 border border-stone-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-xs text-stone-400 mb-1">Passwort</label>
                            <input type="password" id="auth-password" required class="w-full bg-stone-50 border border-stone-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-emerald-500">
                        </div>
                        <p id="auth-error" class="hidden text-xs text-red-600 font-medium"></p>
                        <button type="submit" id="auth-submit-btn" class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-medium py-2 rounded-lg text-sm transition">
                            Anmelden
                        </button>
                    </form>
                    <div class="pt-2 border-t border-stone-100 text-center">
                        <button type="button" onclick="switchAuthMode()" id="auth-switch-btn" class="text-xs text-emerald-700 hover:underline">
                            Noch kein Konto? Jetzt registrieren
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ANSICHT: NEUE PFLANZE HINZUFÜGEN -->
        <div id="add-view" class="hidden p-4 flex flex-col gap-4">
            <button onclick="showList()" class="self-start text-sm text-emerald-700 hover:text-emerald-900 font-medium mb-1">
                ← Abbrechen
            </button>

            <div class="bg-white border border-stone-200 rounded-xl p-5 shadow-sm space-y-4">
                <h2 class="text-lg font-bold">Neue Pflanze hinzufügen</h2>

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
                    <div id="search-results" class="hidden max-h-44 overflow-y-auto border border-stone-200 rounded-lg divide-y divide-stone-100 bg-stone-50"></div>
                </div>

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

                <div class="space-y-2">
                    <div class="relative w-full h-3.5 bg-red-200 rounded-full overflow-visible">
                        <div id="det-bar-ideal" class="absolute top-0 bottom-0 bg-emerald-400 rounded-full transition-all duration-300"></div>
                        <div id="det-bar-marker" class="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 w-4 h-4 bg-stone-900 border-2 border-white rounded-full shadow transition-all duration-300"></div>
                    </div>
                </div>

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

                <div id="api-loading" class="text-center py-4 text-stone-400 text-xs border-t border-stone-100">
                    Lade und übersetze Pflegehinweise...
                </div>

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

    <!-- SCHWEBENDER + BUTTON UNTEN RECHTS (bewegt sich beim Scrollen nicht) -->
    <?php if ($userId): ?>
        <button id="fab-add-btn" onclick="showAddView()" 
                class="fixed bottom-6 right-6 z-30 bg-emerald-700 hover:bg-emerald-800 text-white w-14 h-14 rounded-full font-bold text-2xl flex items-center justify-center shadow-lg transition transform hover:scale-105" 
                title="Pflanze hinzufügen">
            +
        </button>
    <?php endif; ?>

    <script src="app.js"></script>
</body>
</html>