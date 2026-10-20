const PLANTBOOK_API_KEY = '50c7f0e511ce2e0663396dac9c1ad8d8eb1c11eb';
const plantCache = {};
let currentPlant = null;

// Hilfsfunktion: Blendet alle Ansichten aus
function hideAllViews() {
    document.getElementById('main-view').classList.add('hidden');
    document.getElementById('detail-view').classList.add('hidden');
    document.getElementById('add-view').classList.add('hidden');
    document.getElementById('auth-view').classList.add('hidden');
    
    const fab = document.getElementById('fab-add-btn');
    if (fab) fab.classList.add('hidden');
}

// Übersetzt beliebige englische Texte automatisch auf Deutsch
async function translateToGerman(text) {
    if (!text) return 'Keine Angabe';
    try {
        const url = `https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=de&dt=t&q=${encodeURIComponent(text)}`;
        const res = await fetch(url);
        const data = await res.json();
        return data[0].map(part => part[0]).join('');
    } catch (e) {
        return text;
    }
}

function updateDetailBar(min, max, current) {
    const cleanMin = Math.max(0, Math.min(100, Number(min)));
    const cleanMax = Math.max(0, Math.min(100, Number(max)));
    const cleanCur = Math.max(0, Math.min(100, Number(current)));
    const width = Math.max(0, cleanMax - cleanMin);

    const idealBar = document.getElementById('det-bar-ideal');
    const marker = document.getElementById('det-bar-marker');

    idealBar.style.left = `${cleanMin}%`;
    idealBar.style.width = `${width}%`;
    marker.style.left = `${cleanCur}%`;

    document.getElementById('det-ideal-text').textContent = `${cleanMin}% – ${cleanMax}%`;
}

function toggleEditMoisture() {
    document.getElementById('edit-form').classList.toggle('hidden');
}

async function showDetail(plant) {
    currentPlant = plant;
    hideAllViews();
    document.getElementById('detail-view').classList.remove('hidden');
    document.getElementById('edit-form').classList.add('hidden');

    document.getElementById('det-id').value = plant.id;
    document.getElementById('det-nickname').textContent = plant.nickname;
    document.getElementById('det-pid').textContent = plant.plantbook_pid;
    
    document.getElementById('det-min').value = plant.min_moisture;
    document.getElementById('det-max').value = plant.max_moisture;
    updateDetailBar(plant.min_moisture, plant.max_moisture, plant.current_moisture);

    document.getElementById('api-loading').classList.remove('hidden');
    document.getElementById('api-content').classList.add('hidden');

    try {
        const apiData = await fetchPlantbookData(plant.plantbook_pid);
        
        document.getElementById('det-temp').textContent = `${apiData.min_temp}°C – ${apiData.max_temp}°C`;
        document.getElementById('det-humid').textContent = `${apiData.min_env_humid}% – ${apiData.max_env_humid}%`;

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
    location.reload();
}

async function saveMoisture(event) {
    event.preventDefault();
    const btn = document.getElementById('save-btn');
    const newMin = document.getElementById('det-min').value;
    const newMax = document.getElementById('det-max').value;

    btn.textContent = 'Speichere...';

    const formData = new URLSearchParams();
    formData.append('id', document.getElementById('det-id').value);
    formData.append('min_moisture', newMin);
    formData.append('max_moisture', newMax);

    const response = await fetch('update_moisture.php', {
        method: 'POST',
        body: formData
    });

    const result = await response.json();
    if (result.success) {
        updateDetailBar(newMin, newMax, currentPlant.current_moisture);
        btn.textContent = 'Gespeichert! ✓';
        setTimeout(() => {
            btn.textContent = 'Speichern';
            document.getElementById('edit-form').classList.add('hidden');
        }, 800);
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

// =========================================================
// PFLANZE HINZUFÜGEN & PLANTBOOK-SUCHE
// =========================================================

function showAddView() {
    hideAllViews();
    document.getElementById('add-view').classList.remove('hidden');
}

async function searchPlants() {
    const query = document.getElementById('plant-search-input').value.trim();
    const resultsContainer = document.getElementById('search-results');

    if (!query) return;

    resultsContainer.classList.remove('hidden');
    resultsContainer.innerHTML = '<div class="p-3 text-xs text-stone-400">Suche in Open Plantbook...</div>';

    try {
        const url = `https://open.plantbook.io/api/v1/plant/search?alias=${encodeURIComponent(query)}`;
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Authorization': `Token ${PLANTBOOK_API_KEY}`,
                'Content-Type': 'application/json'
            }
        });

        if (!response.ok) throw new Error('Suche fehlgeschlagen');
        const data = await response.json();

        if (!data.results || data.results.length === 0) {
            resultsContainer.innerHTML = '<div class="p-3 text-xs text-stone-400">Keine Pflanze gefunden.</div>';
            return;
        }

        resultsContainer.innerHTML = '';
        data.results.forEach(item => {
            const row = document.createElement('div');
            row.className = 'p-2.5 text-xs hover:bg-emerald-50 cursor-pointer transition flex justify-between items-center';
            row.innerHTML = `
                <div>
                    <span class="font-bold text-stone-800 block">${item.display_pid}</span>
                    <span class="text-stone-400 italic">${item.alias}</span>
                </div>
                <span class="text-emerald-700 font-medium">Auswählen →</span>
            `;
            row.onclick = () => selectPlantFromSearch(item.pid);
            resultsContainer.appendChild(row);
        });
    } catch (error) {
        resultsContainer.innerHTML = '<div class="p-3 text-xs text-red-500">Fehler bei der API-Suche.</div>';
    }
}

async function selectPlantFromSearch(pid) {
    document.getElementById('new-pid').value = pid;
    document.getElementById('search-results').classList.add('hidden');

    try {
        const url = `https://open.plantbook.io/api/v1/plant/detail/${encodeURIComponent(pid)}/`;
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Authorization': `Token ${PLANTBOOK_API_KEY}`,
                'Content-Type': 'application/json'
            }
        });

        if (response.ok) {
            const details = await response.json();
            if (details.min_soil_moist !== undefined) {
                document.getElementById('new-min').value = details.min_soil_moist;
            }
            if (details.max_soil_moist !== undefined) {
                document.getElementById('new-max').value = details.max_soil_moist;
            }
        }
    } catch (e) {}
}

async function createNewPlant(event) {
    event.preventDefault();
    const pid = document.getElementById('new-pid').value.trim();
    const errorEl = document.getElementById('add-error');
    const submitBtn = document.getElementById('add-submit-btn');

    if (!pid) {
        errorEl.textContent = 'Bitte suche und wähle zuerst oben eine Pflanzenart aus.';
        errorEl.classList.remove('hidden');
        return;
    }

    errorEl.classList.add('hidden');
    submitBtn.textContent = 'Speichere...';

    const formData = new URLSearchParams();
    formData.append('plantbook_pid', pid);
    formData.append('min_moisture', document.getElementById('new-min').value);
    formData.append('max_moisture', document.getElementById('new-max').value);
    formData.append('nickname', document.getElementById('new-nickname').value);
    formData.append('esp_device_id', document.getElementById('new-esp').value);

    const response = await fetch('add_plant.php', {
        method: 'POST',
        body: formData
    });

    const result = await response.json();
    if (result.success) {
        showList();
    } else {
        errorEl.textContent = result.error || 'Fehler beim Speichern.';
        errorEl.classList.remove('hidden');
        submitBtn.textContent = 'Pflanze speichern';
    }
}

// =========================================================
// LOGIN, REGISTRIERUNG & LOGOUT
// =========================================================

function toggleAuthView() {
    hideAllViews();
    document.getElementById('auth-view').classList.remove('hidden');
}

function switchAuthMode() {
    const actionInput = document.getElementById('auth-action');
    const title = document.getElementById('auth-title');
    const submitBtn = document.getElementById('auth-submit-btn');
    const switchBtn = document.getElementById('auth-switch-btn');
    const errorEl = document.getElementById('auth-error');

    errorEl.classList.add('hidden');

    if (actionInput.value === 'login') {
        actionInput.value = 'register';
        title.textContent = 'Neues Konto erstellen';
        submitBtn.textContent = 'Registrieren';
        switchBtn.textContent = 'Bereits ein Konto? Jetzt anmelden';
    } else {
        actionInput.value = 'login';
        title.textContent = 'Anmelden';
        submitBtn.textContent = 'Anmelden';
        switchBtn.textContent = 'Noch kein Konto? Jetzt registrieren';
    }
}

async function submitAuth(event) {
    event.preventDefault();
    const errorEl = document.getElementById('auth-error');
    errorEl.classList.add('hidden');

    const formData = new URLSearchParams();
    formData.append('action', document.getElementById('auth-action').value);
    formData.append('username', document.getElementById('auth-username').value);
    formData.append('password', document.getElementById('auth-password').value);

    const response = await fetch('auth.php', {
        method: 'POST',
        body: formData
    });

    const result = await response.json();
    if (result.success) {
        location.reload();
    } else {
        errorEl.textContent = result.error || 'Fehler bei der Anmeldung.';
        errorEl.classList.remove('hidden');
    }
}

async function logoutUser() {
    const formData = new URLSearchParams();
    formData.append('action', 'logout');

    await fetch('auth.php', {
        method: 'POST',
        body: formData
    });
    location.reload();
}