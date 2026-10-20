<?php
require_once 'config.php';

// Alle Pflanzen aus der Tabelle holen (inklusive min_moisture und max_moisture)
$stmt =$pdo->query("SELECT * FROM plants ORDER BY id ASC");
$plants =$stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <header class="bg-white border-b border-stone-200 px-5 py-4 sticky top-0 z-10">
            <h1 class="text-xl font-bold tracking-tight">Gnüüschanne</h1>
        </header>

        <!-- HAUPTANSICHT: Pflanzen-Liste -->
        <main id="main-view" class="p-4 flex flex-col gap-3">
            <?php foreach ($plants as$plant): ?>
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

        <!-- DETAILANSICHT (Standardmässig versteckt) -->
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

                <!-- Formular zum Bearbeiten der idealen Bodenfeuchte (aus Datenbank) -->
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

                <!-- Lade-Indikator / Rest-Tipps von Plantbook API -->
                <div id="api-loading" class="text-center py-4 text-stone-400 text-xs">
                    Lade weitere Infos von Open Plantbook...
                </div>

                <!-- Restliche API Infos (Licht & Temperatur) -->
                <div id="api-content" class="hidden space-y-2 pt-3 border-t border-stone-100 text-sm">
                    <div class="flex justify-between text-xs">
                        <span class="text-stone-500">Lichtbedarf:</span>
                        <span id="det-light" class="font-medium"></span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-stone-500">Temperatur:</span>
                        <span id="det-temp" class="font-medium"></span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        const PLANTBOOK_API_KEY = '50c7f0e511ce2e0663396dac9c1ad8d8eb1c11eb';
        const plantCache = {};

        async function showDetail(plant) {
            document.getElementById('main-view').classList.add('hidden');
            document.getElementById('detail-view').classList.remove('hidden');

            document.getElementById('det-id').value = plant.id;
            document.getElementById('det-nickname').textContent = plant.nickname;
            document.getElementById('det-pid').textContent = plant.plantbook_pid;
            
            // Werte direkt aus der Datenbank in die Input-Felder schreiben
            document.getElementById('det-min').value = plant.min_moisture;
            document.getElementById('det-max').value = plant.max_moisture;
            
            const badge = document.getElementById('det-moisture-badge');
            badge.textContent = plant.current_moisture + '% Ist-Wert';
            badge.className = plant.current_moisture < plant.min_moisture 
                ? 'font-bold px-2.5 py-1 rounded-lg text-sm bg-red-100 text-red-700' 
                : 'font-bold px-2.5 py-1 rounded-lg text-sm bg-stone-100 text-stone-700';

            // Restliche Infos optional von der API laden
            document.getElementById('api-loading').classList.remove('hidden');
            document.getElementById('api-content').classList.add('hidden');

            try {
                const apiData = await fetchPlantbookData(plant.plantbook_pid);
                document.getElementById('det-light').textContent = `${apiData.min_light_lux} – ${apiData.max_light_lux} Lux`;
                document.getElementById('det-temp').textContent = `${apiData.min_temp}°C – ${apiData.max_temp}°C`;

                document.getElementById('api-loading').classList.add('hidden');
                document.getElementById('api-content').classList.remove('hidden');
            } catch (error) {
                document.getElementById('api-loading').classList.add('hidden');
            }
        }

        function showList() {
            document.getElementById('detail-view').classList.add('hidden');
            document.getElementById('main-view').classList.remove('hidden');
            location.reload(); // Aktualisiert die Liste beim Zurückkehren
        }

        async function saveMoisture(event) {
            event.preventDefault();
            const btn = document.getElementById('save-btn');
            btn.textContent = 'Speichere...';

            const formData = new URLSearchParams();
            formData.append('id', document.getElementById('det-id').value);
            formData.append('min_moisture', document.getElementById('det-min').value);
            formData.append('max_moisture', document.getElementById('det-max').value);

            const response = await fetch('update_moisture.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                btn.textContent = 'Erfolgreich gespeichert! ✓';
                setTimeout(() => {
                    btn.textContent = 'Änderungen speichern';
                }, 2000);
            } else {
                btn.textContent = 'Fehler beim Speichern!';
            }
        }

        async function fetchPlantbookData(pid) {
            const cleanPid = pid.trim().toLowerCase();
            if (plantCache[cleanPid]) return plantCache[cleanPid];

            const url = `https://open.plantbook.io/api/v1/plant/detail/${encodeURIComponent(cleanPid)}/`;
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Authorization': `Token ${PLANTBOOK_API_KEY}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) throw new Error('API-Fehler');
            const data = await response.json();
            plantCache[cleanPid] = data;
            return data;
        }
    </script>
</body>
</html>