        // Dein Plantbook API Key
        const PLANTBOOK_API_KEY = '50c7f0e511ce2e0663396dac9c1ad8d8eb1c11eb';
        const plantCache = {};

        async function showDetail(plant) {
            // Ansichten umschalten
            document.getElementById('main-view').classList.add('hidden');
            document.getElementById('detail-view').classList.remove('hidden');

            // Grunddaten setzen
            document.getElementById('det-nickname').textContent = plant.nickname;
            document.getElementById('det-pid').textContent = plant.plantbook_pid;
            
            const badge = document.getElementById('det-moisture-badge');
            badge.textContent = plant.current_moisture + '%';
            badge.className = plant.current_moisture < plant.min_moisture 
                ? 'font-bold px-2.5 py-1 rounded-lg text-sm bg-red-100 text-red-700' 
                : 'font-bold px-2.5 py-1 rounded-lg text-sm bg-stone-100 text-stone-700';

            // UI für API-Laden vorbereiten
            document.getElementById('api-loading').classList.remove('hidden');
            document.getElementById('api-content').classList.add('hidden');

            try {
                const apiData = await fetchPlantbookData(plant.plantbook_pid);
                
                // API-Daten anzeigen
                document.getElementById('det-ideal-moist').textContent = `${apiData.min_soil_moist}% – ${apiData.max_soil_moist}%`;
                document.getElementById('det-light').textContent = `${apiData.min_light_lux} – ${apiData.max_light_lux} Lux`;
                document.getElementById('det-temp').textContent = `${apiData.min_temp}°C – ${apiData.max_temp}°C`;
                document.getElementById('det-humid').textContent = `${apiData.min_env_humid}% – ${apiData.max_env_humid}%`;

                document.getElementById('api-loading').classList.add('hidden');
                document.getElementById('api-content').classList.remove('hidden');
            } catch (error) {
                document.getElementById('api-loading').textContent = 'Konnte keine Zusatzinfos von Plantbook laden.';
            }
        }

        function showList() {
            document.getElementById('detail-view').classList.add('hidden');
            document.getElementById('main-view').classList.remove('hidden');
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