const PLANTBOOK_API_KEY = '50c7f0e511ce2e0663396dac9c1ad8d8eb1c11eb';
const plantCache = {};

// Übersetzt beliebige englische Texte automatisch auf Deutsch
async function translateToGerman(text) {
    if (!text) return 'Keine Angabe';
    try {
        const url = `https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=de&dt=t&q=${encodeURIComponent(text)}`;
        const res = await fetch(url);
        const data = await res.json();
        // Setzt alle übersetzten Satzteile sauber zusammen
        return data[0].map(part => part[0]).join('');
    } catch (e) {
        return text; // Falls offline, zeige den englischen Originaltext
    }
}

async function showDetail(plant) {
    document.getElementById('main-view').classList.add('hidden');
    document.getElementById('detail-view').classList.remove('hidden');

    document.getElementById('det-id').value = plant.id;
    document.getElementById('det-nickname').textContent = plant.nickname;
    document.getElementById('det-pid').textContent = plant.plantbook_pid;
    
    // Datenbank-Werte für Bodenfeuchte in die Felder füllen
    document.getElementById('det-min').value = plant.min_moisture;
    document.getElementById('det-max').value = plant.max_moisture;
    
    const badge = document.getElementById('det-moisture-badge');
    badge.textContent = plant.current_moisture + '% Ist-Wert';
    badge.className = plant.current_moisture < plant.min_moisture 
        ? 'font-bold px-2.5 py-1 rounded-lg text-sm bg-red-100 text-red-700' 
        : 'font-bold px-2.5 py-1 rounded-lg text-sm bg-stone-100 text-stone-700';

    document.getElementById('api-loading').classList.remove('hidden');
    document.getElementById('api-content').classList.add('hidden');

    try {
        const apiData = await fetchPlantbookData(plant.plantbook_pid);
        
        // Klima-Werte setzen
        document.getElementById('det-temp').textContent = `${apiData.min_temp}°C – ${apiData.max_temp}°C`;
        document.getElementById('det-humid').textContent = `${apiData.min_env_humid}% – ${apiData.max_env_humid}%`;

        // Care Information setzen (bereits auf Deutsch übersetzt)
        document.getElementById('det-light').textContent = apiData.sunlight_de;
        document.getElementById('det-fertilization').textContent = apiData.fertilization_de;
        document.getElementById('det-pruning').textContent = apiData.pruning_de;

        document.getElementById('api-loading').classList.add('hidden');
        document.getElementById('api-content').classList.remove('hidden');
    } catch (error) {
        document.getElementById('api-loading').textContent = 'Konnte API-Infos nicht laden.';
    }
}

function showList() {
    document.getElementById('detail-view').classList.add('hidden');
    document.getElementById('main-view').classList.remove('hidden');
    location.reload();
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

    const url = `https://open.plantbook.io/api/v1/plant/detail/${encodeURIComponent(cleanPid)}/?include=care`;
    const response = await fetch(url, {
        method: 'GET',
        headers: {
            'Authorization': `Token ${PLANTBOOK_API_KEY}`,
            'Content-Type': 'application/json'
        }
    });

    if (!response.ok) throw new Error('API-Fehler');
    const data = await response.json();

    // Alle 3 englischen Care-Texte gleichzeitig auf Deutsch übersetzen
    const [lightDe, fertDe, pruneDe] = await Promise.all([
        translateToGerman(data.sunlight),
        translateToGerman(data.fertilization),
        translateToGerman(data.pruning)
    ]);

    data.sunlight_de = lightDe;
    data.fertilization_de = fertDe;
    data.pruning_de = pruneDe;

    plantCache[cleanPid] = data;
    return data;
}