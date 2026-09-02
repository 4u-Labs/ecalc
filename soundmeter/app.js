// --- CONSTANTS AND APP STATE ---
const validBrazilianDdds = [
    "11", "12", "13", "14", "15", "16", "17", "18", "19",
    "21", "22", "24", "27", "28",
    "31", "32", "33", "34", "35", "37", "38",
    "41", "42", "43", "44", "45", "46", "47", "48", "49",
    "51", "53", "54", "55",
    "61", "62", "63", "64", "65", "66", "67", "68", "69",
    "71", "73", "74", "75", "77", "79",
    "81", "82", "83", "84", "85", "86", "87", "88", "89",
    "91", "92", "93", "94", "95", "96", "97", "98", "99"
];

// App Configurations
let audioContext = null;
let analyser = null;
let microphoneSource = null;
let javascriptNode = null;
let isCapturing = false;
let animationFrameId = null;

// Calibration & Admin Settings
let baseOffset = 87.0; // Base adjustment to align mic RMS to real dBA
let userCalibrationOffset = 0.0; // Dynamic slider offset (-20 to +20)
let sampleInterval = 50; // Update rate in milliseconds

// History Data for Chart & Exports
let dbHistory = []; // Array of { time: Date, db: number }
const maxHistorySize = 100; // Limit points on active graph

// Statistical Metrics
let minDb = Infinity;
let maxDb = -Infinity;
let sumOfPressures = 0; // For Logarithmic Average (Leq)
let sampleCount = 0;

// Canvas Chart Configuration
const canvas = document.getElementById("noise-chart");
const ctx = canvas.getContext("2d");

// --- DOM ELEMENTS ---
const permissionPrompt = document.getElementById("permission-prompt");
const btnRequestPermission = document.getElementById("btn-request-permission");
const dbDisplay = document.getElementById("db-display");
const noiseClass = document.getElementById("noise-class");
const gaugeFill = document.getElementById("gauge-fill");

const statMin = document.getElementById("stat-min");
const statAvg = document.getElementById("stat-avg");
const statMax = document.getElementById("stat-max");

const btnToggleCapture = document.getElementById("btn-toggle-capture");
const btnReset = document.getElementById("btn-reset");

const calibrationSlider = document.getElementById("calibration-slider");
const calibrationValue = document.getElementById("calibration-value");

// Tabs Elements
const tabNr15 = document.getElementById("tab-nr15");
const tabNbr10151 = document.getElementById("tab-nbr10151");
const contentNr15 = document.getElementById("content-nr15");
const contentNbr10151 = document.getElementById("content-nbr10151");

// Diagnostics & Filters
const zoneSelect = document.getElementById("zone-select");
const periodSelect = document.getElementById("period-select");
const nr15Diagnostic = document.getElementById("nr15-diagnostic");
const nbrDiagnostic = document.getElementById("nbr-diagnostic");

// Admin Modal Elements
const logoLink = document.getElementById("logo-link");
const adminLoginModal = document.getElementById("admin-login-modal");
const adminPanelModal = document.getElementById("admin-panel-modal");
const btnCloseLogin = document.getElementById("btn-close-login");
const btnCloseAdminPanel = document.getElementById("btn-close-admin-panel");
const adminLoginForm = document.getElementById("admin-login-form");
const adminUsernameInput = document.getElementById("admin-username");
const adminPasswordInput = document.getElementById("admin-password");
const loginErrorMsg = document.getElementById("login-error-msg");

const adminBaseOffset = document.getElementById("admin-base-offset");
const adminUpdateInterval = document.getElementById("admin-update-interval");
const btnSaveAdminSettings = document.getElementById("btn-save-admin-settings");
const btnExportJson = document.getElementById("btn-export-json");
const btnExportCsv = document.getElementById("btn-export-csv");

const diagSampleRate = document.getElementById("diag-sample-rate");
const diagFftSize = document.getElementById("diag-fft-size");
const diagAudioState = document.getElementById("diag-audio-state");

// --- CANVAS RESPONSIVENESS ---
function resizeCanvas() {
    const container = canvas.parentElement;
    canvas.width = container.clientWidth;
    canvas.height = container.clientHeight;
    drawChart();
}
window.addEventListener("resize", resizeCanvas);
setTimeout(resizeCanvas, 100);

// --- TAB SWITCHING ---
tabNr15.addEventListener("click", () => {
    tabNr15.classList.add("active");
    tabNbr10151.classList.remove("active");
    contentNr15.classList.add("active");
    contentNbr10151.classList.remove("active");
});

tabNbr10151.addEventListener("click", () => {
    tabNbr10151.classList.add("active");
    tabNr15.classList.remove("active");
    contentNbr10151.classList.add("active");
    contentNr15.classList.remove("active");
});

// --- AUDIO CAPTURE LOGIC (WEB AUDIO API) ---
async function initAudio() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
        permissionPrompt.classList.add("hidden");
        
        // Setup Web Audio Context
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
        analyser = audioContext.createAnalyser();
        analyser.fftSize = 2048;
        analyser.smoothingTimeConstant = 0.4;
        
        microphoneSource = audioContext.createMediaStreamSource(stream);
        microphoneSource.connect(analyser);
        
        // Enable Controls
        btnToggleCapture.removeAttribute("disabled");
        btnReset.removeAttribute("disabled");
        
        document.getElementById("mic-status-dot").className = "status-dot online";
        document.getElementById("mic-status-text").textContent = "Conectado";
        
        // Start capture immediately
        startCapture();
    } catch (err) {
        console.error("Erro ao obter acesso ao microfone:", err);
        alert("Não foi possível acessar o microfone. Certifique-se de que deu as permissões necessárias no seu navegador.");
    }
}

btnRequestPermission.addEventListener("click", initAudio);

function startCapture() {
    if (isCapturing) return;
    isCapturing = true;
    btnToggleCapture.innerHTML = '<i class="fa-solid fa-pause"></i> Pausar';
    btnToggleCapture.classList.add("btn-secondary");
    
    if (audioContext && audioContext.state === "suspended") {
        audioContext.resume();
    }
    
    processAudio();
}

function pauseCapture() {
    if (!isCapturing) return;
    isCapturing = false;
    btnToggleCapture.innerHTML = '<i class="fa-solid fa-play"></i> Iniciar';
    btnToggleCapture.classList.remove("btn-secondary");
    
    if (audioContext && audioContext.state === "running") {
        audioContext.suspend();
    }
    
    if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
    }
}

btnToggleCapture.addEventListener("click", () => {
    if (isCapturing) {
        pauseCapture();
    } else {
        startCapture();
    }
});

btnReset.addEventListener("click", () => {
    minDb = Infinity;
    maxDb = -Infinity;
    sumOfPressures = 0;
    sampleCount = 0;
    dbHistory = [];
    
    statMin.textContent = "--";
    statMax.textContent = "--";
    statAvg.textContent = "--";
    dbDisplay.textContent = "00.0";
    
    // Reset Gauge
    gaugeFill.style.strokeDashoffset = 534;
    
    // Clear reference highlights
    document.querySelectorAll(".ref-table tr").forEach(row => row.classList.remove("active-limit"));
    
    drawChart();
});

// --- DECIBEL CALCULATION ---
let lastSampleTime = 0;

function processAudio() {
    if (!isCapturing) return;
    
    animationFrameId = requestAnimationFrame(processAudio);
    
    const now = Date.now();
    if (now - lastSampleTime < sampleInterval) return;
    lastSampleTime = now;
    
    const bufferLength = analyser.frequencyBinCount;
    const dataArray = new Float32Array(bufferLength);
    analyser.getFloatTimeDomainData(dataArray);
    
    // Calculate RMS (Root Mean Square)
    let sum = 0;
    for (let i = 0; i < bufferLength; i++) {
        const val = dataArray[i];
        sum += val * val;
    }
    const rms = Math.sqrt(sum / bufferLength);
    
    // Convert to Decibels
    let db = 20 * Math.log10(rms);
    if (db === -Infinity || isNaN(db)) {
        db = -120; // Lower bound
    }
    
    // Apply calibration offset to convert to relative dBA
    let dba = db + baseOffset + parseFloat(userCalibrationOffset);
    
    // Clamp values between 30 and 120 dBA for UI
    if (dba < 30) dba = 30 + Math.random() * 2; // simulate ambient baseline noise
    if (dba > 120) dba = 120;
    
    updateUI(dba);
}

// --- UI UPDATING LOGIC ---
function updateUI(db) {
    const formattedDb = db.toFixed(1);
    dbDisplay.textContent = formattedDb;
    
    // Update Gauge Fill
    const minRange = 30;
    const maxRange = 120;
    const percent = Math.min(Math.max((db - minRange) / (maxRange - minRange), 0), 1);
    const strokeArray = 534;
    const offset = strokeArray - (percent * strokeArray);
    gaugeFill.style.strokeDashoffset = offset;
    
    // Determine Noise Category
    let levelClass = "level-safe";
    let badgeClass = "badge-safe";
    let category = "Silencioso";
    
    if (db >= 85) {
        levelClass = "level-danger";
        badgeClass = "badge-danger";
        category = "Nocivo / Perigoso";
    } else if (db >= 70) {
        levelClass = "level-warning";
        badgeClass = "badge-warning";
        category = "Barulhento";
    } else if (db >= 50) {
        levelClass = "level-warning";
        badgeClass = "badge-warning";
        category = "Moderado";
    }
    
    gaugeFill.className.baseVal = `gauge-fill ${levelClass}`;
    noiseClass.className = `badge ${badgeClass}`;
    noiseClass.textContent = category;
    
    // Update Statistics
    if (db < minDb && db > 30.5) minDb = db;
    if (db > maxDb) maxDb = db;
    
    // Logarithmic Average (Leq)
    sumOfPressures += Math.pow(10, db / 10);
    sampleCount++;
    const averageDb = 10 * Math.log10(sumOfPressures / sampleCount);
    
    statMin.textContent = minDb === Infinity ? "--" : minDb.toFixed(1);
    statMax.textContent = maxDb === -Infinity ? "--" : maxDb.toFixed(1);
    statAvg.textContent = isNaN(averageDb) ? "--" : averageDb.toFixed(1);
    
    // Add to history
    dbHistory.push({ time: new Date(), db: db });
    if (dbHistory.length > maxHistorySize) {
        dbHistory.shift();
    }
    
    // Redraw chart
    drawChart();
    
    // Run diagnostics and update legislations
    updateLegislation(db, averageDb);
}

// --- DYNAMIC CANVAS CHART DRAWING ---
function drawChart() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    if (dbHistory.length === 0) {
        ctx.fillStyle = "rgba(255, 255, 255, 0.1)";
        ctx.font = "14px Inter";
        ctx.textAlign = "center";
        ctx.fillText("Aguardando captura de áudio...", canvas.width / 2, canvas.height / 2);
        return;
    }
    
    const margin = { top: 10, right: 10, bottom: 20, left: 35 };
    const width = canvas.width - margin.left - margin.right;
    const height = canvas.height - margin.top - margin.bottom;
    
    // Draw Grid Lines (Horizontal only for simplicity and premium look)
    ctx.strokeStyle = "rgba(255, 255, 255, 0.03)";
    ctx.lineWidth = 1;
    const gridLines = 4;
    for (let i = 0; i <= gridLines; i++) {
        const y = margin.top + (height / gridLines) * i;
        ctx.beginPath();
        ctx.moveTo(margin.left, y);
        ctx.lineTo(canvas.width - margin.right, y);
        ctx.stroke();
        
        // Draw Y Axis Labels
        const dbVal = Math.round(120 - ((120 - 30) / gridLines) * i);
        ctx.fillStyle = "rgba(255, 255, 255, 0.3)";
        ctx.font = "9px monospace";
        ctx.textAlign = "right";
        ctx.fillText(`${dbVal} dB`, margin.left - 8, y + 3);
    }
    
    // Draw Graph Path
    ctx.beginPath();
    const pointsCount = dbHistory.length;
    
    dbHistory.forEach((pt, index) => {
        const x = margin.left + (width / (maxHistorySize - 1)) * (maxHistorySize - pointsCount + index);
        const y = margin.top + height * (1 - (pt.db - 30) / (120 - 30));
        
        if (index === 0) {
            ctx.moveTo(x, y);
        } else {
            ctx.lineTo(x, y);
        }
    });
    
    // Save line path for stroke
    ctx.strokeStyle = "#4facfe";
    ctx.shadowColor = "rgba(79, 172, 254, 0.5)";
    ctx.shadowBlur = 10;
    ctx.lineWidth = 3;
    ctx.stroke();
    
    // Reset shadow for gradient fill
    ctx.shadowBlur = 0;
    
    // Create fill path
    if (pointsCount > 0) {
        const firstX = margin.left + (width / (maxHistorySize - 1)) * (maxHistorySize - pointsCount);
        const lastX = margin.left + width;
        ctx.lineTo(lastX, margin.top + height);
        ctx.lineTo(firstX, margin.top + height);
        ctx.closePath();
        
        // Gradient Fill
        const gradient = ctx.createLinearGradient(0, margin.top, 0, margin.top + height);
        gradient.addColorStop(0, "rgba(79, 172, 254, 0.3)");
        gradient.addColorStop(1, "rgba(0, 242, 254, 0)");
        ctx.fillStyle = gradient;
        ctx.fill();
    }
}

// --- CALIBRATION SLIDER ---
calibrationSlider.addEventListener("input", (e) => {
    userCalibrationOffset = e.target.value;
    const sign = userCalibrationOffset > 0 ? "+" : "";
    calibrationValue.textContent = `${sign}${userCalibrationOffset} dB`;
});

// --- LEGISLATION COMPLIANCE CHECKS ---
function updateLegislation(db, averageDb) {
    // 1. NR-15 (Labor Law)
    const nrStatusTitle = document.getElementById("nr15-status-title");
    const nrStatusDesc = document.getElementById("nr15-status-desc");
    const nrLimitValue = document.getElementById("nr15-limit-value");
    
    let activeLimitRow = null;
    let maxAllowedTime = "Sem Limite";
    
    if (db >= 115) {
        maxAllowedTime = "7 minutos";
        nr15Diagnostic.className = "legislation-diagnostic diag-danger";
        nrStatusTitle.textContent = "Nível Crítico / Proibitivo";
        nrStatusDesc.textContent = "Limite extremo ultrapassado! Exposição sem proteção auricular imediata é ilegal.";
        activeLimitRow = "115";
    } else if (db >= 110) {
        maxAllowedTime = "15 minutos";
        nr15Diagnostic.className = "legislation-diagnostic diag-danger";
        nrStatusTitle.textContent = "Perigo Imediato";
        nrStatusDesc.textContent = "Nível de ruído muito alto. Exposição máxima de 15 minutos diários.";
        activeLimitRow = "110";
    } else if (db >= 105) {
        maxAllowedTime = "30 minutos";
        nr15Diagnostic.className = "legislation-diagnostic diag-danger";
        nrStatusTitle.textContent = "Risco Elevado";
        nrStatusDesc.textContent = "Ruído excessivo. Limite máximo diário de 30 minutos.";
        activeLimitRow = "105";
    } else if (db >= 100) {
        maxAllowedTime = "1 hora";
        nr15Diagnostic.className = "legislation-diagnostic diag-danger";
        nrStatusTitle.textContent = "Risco Elevado";
        nrStatusDesc.textContent = "Ruído acima do limite seguro. Exposição máxima permitida de 1 hora.";
        activeLimitRow = "100";
    } else if (db >= 95) {
        maxAllowedTime = "2 horas";
        nr15Diagnostic.className = "legislation-diagnostic diag-warning";
        nrStatusTitle.textContent = "Ruído Insalubre";
        nrStatusDesc.textContent = "Insalubridade média. Exposição máxima recomendada de 2 horas.";
        activeLimitRow = "95";
    } else if (db >= 90) {
        maxAllowedTime = "4 horas";
        nr15Diagnostic.className = "legislation-diagnostic diag-warning";
        nrStatusTitle.textContent = "Ruído Insalubre";
        nrStatusDesc.textContent = "Ambiente insalubre. Exposição máxima permitida de 4 horas diárias.";
        activeLimitRow = "90";
    } else if (db >= 88) {
        maxAllowedTime = "5 horas";
        nr15Diagnostic.className = "legislation-diagnostic diag-warning";
        nrStatusTitle.textContent = "Limite de Tolerância";
        nrStatusDesc.textContent = "Ruído moderadamente alto. Limite diário de 5 horas.";
        activeLimitRow = "88";
    } else if (db >= 87) {
        maxAllowedTime = "6 horas";
        nr15Diagnostic.className = "legislation-diagnostic diag-warning";
        nrStatusTitle.textContent = "Limite de Tolerância";
        nrStatusDesc.textContent = "Ruído atingindo patamar de tolerância. Limite de 6 horas.";
        activeLimitRow = "87";
    } else if (db >= 86) {
        maxAllowedTime = "7 horas";
        nr15Diagnostic.className = "legislation-diagnostic diag-warning";
        nrStatusTitle.textContent = "Limite de Tolerância";
        nrStatusDesc.textContent = "Ruído limítrofe. Exposição permitida de até 7 horas.";
        activeLimitRow = "86";
    } else if (db >= 85) {
        maxAllowedTime = "8 horas";
        nr15Diagnostic.className = "legislation-diagnostic diag-warning";
        nrStatusTitle.textContent = "Limite Máximo Seguro";
        nrStatusDesc.textContent = "Limite legal de insalubridade sem equipamento de proteção (EPI). Máximo 8 horas.";
        activeLimitRow = "85";
    } else {
        maxAllowedTime = "Sem Limite";
        nr15Diagnostic.className = "legislation-diagnostic diag-safe";
        nrStatusTitle.textContent = "Zona Segura";
        nrStatusDesc.textContent = "Nível acústico adequado para jornada completa sem necessidade de EPIs.";
    }
    
    nrLimitValue.textContent = maxAllowedTime;
    
    // Highlight correct row in NR15 Table
    document.querySelectorAll("#content-nr15 tbody tr").forEach(row => {
        row.classList.remove("active-limit");
        if (row.getAttribute("data-limit") === activeLimitRow) {
            row.classList.add("active-limit");
        }
    });

    // 2. NBR 10151 (Convivência / Vizinhança)
    const zone = zoneSelect.value;
    const period = periodSelect.value;
    
    const nbrStatusTitle = document.getElementById("nbr-status-title");
    const nbrStatusDesc = document.getElementById("nbr-status-desc");
    const nbrLimitValue = document.getElementById("nbr-limit-value");
    
    // Limits Grid: Residential, Mixed, Leisure, Industrial
    const nbrLimits = {
        residential: { day: 50, night: 45 },
        mixed: { day: 55, night: 50 },
        leisure: { day: 65, night: 55 },
        industrial: { day: 70, night: 60 }
    };
    
    const currentLimit = nbrLimits[zone][period];
    nbrLimitValue.textContent = `${currentLimit} dBA`;
    
    // Highlight correct row in NBR Table
    document.querySelectorAll("#content-nbr10151 tbody tr").forEach(row => {
        row.classList.remove("active-limit");
        if (row.getAttribute("data-zone") === zone) {
            row.classList.add("active-limit");
        }
    });
    
    // Compare against AVERAGE noise (Leq is correct for NBR 10151 assessments)
    const compareDb = isNaN(averageDb) ? db : averageDb;
    
    if (compareDb > currentLimit + 5) {
        nbrDiagnostic.className = "legislation-diagnostic diag-danger";
        nbrStatusTitle.textContent = "Limite Excedido (Perturbação)";
        nbrStatusDesc.textContent = `A média de ruído (${compareDb.toFixed(1)} dB) está muito acima da lei municipal para este horário. Risco de denúncia!`;
    } else if (compareDb > currentLimit) {
        nbrDiagnostic.className = "legislation-diagnostic diag-warning";
        nbrStatusTitle.textContent = "Limite de Tolerância Atingido";
        nbrStatusDesc.textContent = `O nível está ligeiramente acima da recomendação (${compareDb.toFixed(1)} dB). Reduza emissões sonoras.`;
    } else {
        nbrDiagnostic.className = "legislation-diagnostic diag-safe";
        nbrStatusTitle.textContent = "Em Conformidade com a Lei";
        nbrStatusDesc.textContent = `A média de ruído (${compareDb.toFixed(1)} dB) respeita o zoneamento e horário local. Vizinhança pacífica.`;
    }
}

zoneSelect.addEventListener("change", () => {
    if (dbHistory.length > 0) {
        const avg = parseFloat(statAvg.textContent);
        updateLegislation(dbHistory[dbHistory.length - 1].db, avg);
    }
});

periodSelect.addEventListener("change", () => {
    if (dbHistory.length > 0) {
        const avg = parseFloat(statAvg.textContent);
        updateLegislation(dbHistory[dbHistory.length - 1].db, avg);
    }
});

// --- EASTER EGG LOGO LINK (5 CLIQUES PARA CONFIG ADMINS Ocultas) ---
if (logoLink) {
    logoLink.addEventListener("click", function(e) {
        const now = Date.now();
        let clicks = parseInt(localStorage.getItem("logo_clicks") || "0");
        let lastClick = parseInt(localStorage.getItem("logo_last_click") || "0");

        if (now - lastClick < 2000) {
            clicks++;
        } else {
            clicks = 1;
        }

        localStorage.setItem("logo_clicks", clicks);
        localStorage.setItem("logo_last_click", now);

        if (clicks >= 5) {
            e.preventDefault();
            localStorage.removeItem("logo_clicks");
            localStorage.removeItem("logo_last_click");
            
            // Show administrative login modal
            openAdminLoginModal();
            return;
        }

        // Prevent full page reload if user is already on Sound Meter page
        const targetUrl = this.href;
        const currentUrl = window.location.href;
        if (targetUrl.replace(/\/$/, "") === currentUrl.replace(/\/$/, "")) {
            e.preventDefault();
        }
    });
}

function openAdminLoginModal() {
    adminLoginModal.classList.remove("hidden");
    adminUsernameInput.value = "";
    adminPasswordInput.value = "";
    loginErrorMsg.classList.add("hidden");
}

btnCloseLogin.addEventListener("click", () => {
    adminLoginModal.classList.add("hidden");
});

btnCloseAdminPanel.addEventListener("click", () => {
    adminPanelModal.classList.add("hidden");
});

// Admin credentials authentication (Fbraga / F.braga1 based on hidden-admin-setup requirements)
adminLoginForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const user = adminUsernameInput.value.trim();
    const pass = adminPasswordInput.value;
    
    if (user === "Fbraga" && pass === "F.braga1") {
        adminLoginModal.classList.add("hidden");
        openAdminPanel();
    } else {
        loginErrorMsg.classList.remove("hidden");
    }
});

function openAdminPanel() {
    adminPanelModal.classList.remove("hidden");
    adminBaseOffset.value = baseOffset;
    adminUpdateInterval.value = sampleInterval;
    
    // Populate Diagnostics Table
    if (audioContext) {
        diagSampleRate.textContent = `${audioContext.sampleRate} Hz`;
        diagFftSize.textContent = analyser.fftSize;
        diagAudioState.textContent = audioContext.state.toUpperCase();
    }
}

// Save Admin Adjustments
btnSaveAdminSettings.addEventListener("click", () => {
    baseOffset = parseFloat(adminBaseOffset.value);
    sampleInterval = parseInt(adminUpdateInterval.value);
    adminPanelModal.classList.add("hidden");
    
    // Reset statistical calculations to re-evaluate with new offset
    btnReset.click();
    
    alert("Configurações aplicadas com sucesso! Histórico recalculado.");
});

// --- DATA EXPORTS ---
btnExportJson.addEventListener("click", () => {
    if (dbHistory.length === 0) {
        alert("Sem medições registradas para exportar.");
        return;
    }
    
    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(dbHistory, null, 2));
    const downloadAnchor = document.createElement('a');
    downloadAnchor.setAttribute("href", dataStr);
    downloadAnchor.setAttribute("download", `sound_meter_historico_${Date.now()}.json`);
    document.body.appendChild(downloadAnchor);
    downloadAnchor.click();
    downloadAnchor.remove();
});

btnExportCsv.addEventListener("click", () => {
    if (dbHistory.length === 0) {
        alert("Sem medições registradas para exportar.");
        return;
    }
    
    let csvContent = "data:text/csv;charset=utf-8,Timestamp,Nivel_Ruido_dBA\n";
    dbHistory.forEach(pt => {
        csvContent += `${pt.time.toISOString()},${pt.db.toFixed(2)}\n`;
    });
    
    const encodedUri = encodeURI(csvContent);
    const downloadAnchor = document.createElement('a');
    downloadAnchor.setAttribute("href", encodedUri);
    downloadAnchor.setAttribute("download", `sound_meter_historico_${Date.now()}.csv`);
    document.body.appendChild(downloadAnchor);
    downloadAnchor.click();
    downloadAnchor.remove();
});
