<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIFASMAT - Command Center Display Monitor</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        :root {
            --bg-dark: #0b0f19;
            --card-bg: rgba(18, 24, 38, 0.7);
            --card-border: rgba(255, 255, 255, 0.12);
        }

        * { box-sizing: border-box; }

        html, body {
            height: 100vh;
            width: 100vw;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(at 0% 0%, rgba(14, 165, 233, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(15, 23, 42, 0.9) 0px, transparent 50%);
            color: #ffffff;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            padding: 14px 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* OVERLAY PROMPT FULLSCREEN */
        #fullscreen-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(11, 15, 25, 0.92);
            backdrop-filter: blur(12px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* HEADER MONITOR */
        .monitor-header {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 8px 20px;
            height: 52px;
            flex-shrink: 0;
        }

        .digital-clock {
            font-size: 1.35rem;
            font-weight: 800;
            color: #38bdf8;
            letter-spacing: 1px;
        }

        /* CONTAINER GRID MEMENUHI 1 HALAMAN */
        .grid-wrapper {
            flex-grow: 1;
            margin: 10px 0;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(3, 1fr);
            gap: 12px;
            height: calc(100vh - 120px);
        }

        /* KARTU SERAGAM (UNIFORM STYLE) */
        .monitor-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 14px 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
        }

        .mat-category {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #38bdf8;
            font-weight: 800;
        }

        .mat-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .stat-label {
            font-size: 0.65rem;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        /* ANGKA BESAR, TAJAM & JELAS */
        .stat-value-main {
            font-size: 1.85rem;
            font-weight: 900;
            color: #ffffff;
            line-height: 1;
            letter-spacing: -0.5px;
        }

        .unit-text {
            font-size: 0.85rem;
            font-weight: 700;
            color: #cbd5e1;
            margin-left: 4px;
        }

        .badge-daya-tahan {
            font-size: 0.75rem;
            font-weight: 800;
            padding: 5px 10px;
            border-radius: 6px;
        }

        /* FOOTER MONITOR */
        .monitor-footer {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid var(--card-border);
            border-radius: 10px;
            padding: 6px 18px;
            height: 40px;
            flex-shrink: 0;
        }

        .fade-in-content {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    {{-- OVERLAY ACTION FULLSCREEN --}}
    <div id="fullscreen-overlay">
        <div class="text-center p-4 border border-info border-opacity-30 rounded-4 bg-black bg-opacity-90 shadow-lg position-relative" style="max-width: 480px;">
            <i class="fa-solid fa-desktop text-info display-3 mb-3"></i>
            <h4 class="text-white fw-extrabold mb-2">DISPLAY MONITOR SIAP</h4>
            <p class="text-white-50 mb-4 small">
                Klik tombol di bawah untuk menampilkan dalam mode <strong class="text-info">FULLSCREEN</strong>.
            </p>

            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-info text-dark fw-bold px-4 py-2 shadow-sm" id="btn-trigger-fullscreen">
                    <i class="fa-solid fa-expand me-1"></i> Mode Fullscreen
                </button>
                <button type="button" class="btn btn-outline-secondary text-white border-secondary fw-semibold px-3 py-2" id="btn-cancel-overlay">
                    <i class="fa-solid fa-xmark me-1"></i> Batal
                </button>
            </div>
        </div>
    </div>

    {{-- HEADER MONITOR --}}
    <div class="monitor-header d-flex justify-content-between align-items-center shadow-lg">
        <div class="d-flex align-items-center">
            <div class="p-1 bg-info bg-opacity-20 border border-info border-opacity-30 rounded-2 me-2 px-2">
                <i class="fa-solid fa-desktop text-info fs-6"></i>
            </div>
            <div>
                <h6 class="fw-extrabold mb-0 text-white" style="letter-spacing: 0.5px;">PEMANTAUAN STOK MATERIAL GUDANG</h6>
            </div>
        </div>

        <div class="d-flex align-items-center gap-4">
            <div class="text-end border-end border-secondary border-opacity-50 pe-4">
                <span class="stat-label me-1">TOTAL MATERIAL:</span>
                <span class="fw-bold text-white fs-6" id="txt-total-items">0 Item</span>
            </div>
            <div class="text-end">
                <div class="digital-clock" id="txt-live-clock">00:00:00</div>
            </div>
        </div>
    </div>

    {{-- AREA KARTU DISPLAY MONITOR (GRID 3 BARIS X 3 KOLOM) --}}
    <div class="grid-wrapper" id="display-grid-container">
        <div class="col-12 text-center py-5" style="grid-column: span 3;">
            <div class="spinner-border text-info mb-3" style="width: 2.5rem; height: 2.5rem;" role="status"></div>
            <h6 class="text-white opacity-75">Memuat data stok real-time...</h6>
        </div>
    </div>

    {{-- FOOTER MONITOR & INDIKATOR --}}
    <div class="monitor-footer d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <span class="spinner-grow spinner-grow-sm text-success" role="status" style="width: 0.5rem; height: 0.5rem;"></span>
            <small class="text-white-50" style="font-size: 0.72rem;">
                Status Server: <span class="text-success fw-bold">ONLINE</span> | Refresh: <span id="txt-refresh-timer">--</span>s
            </small>
        </div>

        <div class="d-flex align-items-center gap-3">
            <small class="text-white-50 fw-semibold" style="font-size: 0.72rem;" id="txt-page-indicator">
                Halaman 1 dari 1
            </small>
            <button type="button" class="btn btn-sm btn-outline-light border-opacity-25 py-0 px-2" id="btn-fullscreen-toggle" style="font-size: 0.7rem;">
                <i class="fa-solid fa-expand me-1"></i> Fullscreen
            </button>
        </div>
    </div>

    {{-- JAVASCRIPT ENGINE MONITOR --}}
    <script>
        const API_URL = "{{ route('display.api.data') }}";
        
        let allDisplayData = [];
        let globalSettings = {
            refresh_interval: 30,
            auto_slide: true,
            slide_interval: 10,
            items_per_page: 9
        };

        let currentPage = 1;
        let totalPages = 1;
        let slideTimer = null;
        let refreshCountdown = 30;
        let refreshTimer = null;

        // PEMICU FULLSCREEN
        const fsOverlay = document.getElementById('fullscreen-overlay');
        const btnTriggerFs = document.getElementById('btn-trigger-fullscreen');
        const btnCancelFs = document.getElementById('btn-cancel-overlay');

        function requestNativeFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().then(() => {
                    if (fsOverlay) fsOverlay.style.display = 'none';
                }).catch((err) => {
                    if (fsOverlay) fsOverlay.style.display = 'none';
                });
            } else {
                if (fsOverlay) fsOverlay.style.display = 'none';
            }
        }

        btnTriggerFs?.addEventListener('click', requestNativeFullscreen);
        btnCancelFs?.addEventListener('click', () => {
            if (fsOverlay) fsOverlay.style.display = 'none';
        });

        document.addEventListener('fullscreenchange', () => {
            if (document.fullscreenElement) {
                if (fsOverlay) fsOverlay.style.display = 'none';
            }
        });

        // JAM DIGITAL
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('id-ID', { hour12: false });
            document.getElementById('txt-live-clock').innerText = timeStr;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // FETCH DATA VIA API
        async function fetchMonitorData() {
            try {
                const response = await fetch(API_URL);
                const result = await response.json();

                if (result) {
                    globalSettings = result.settings;
                    allDisplayData = result.data || [];

                    document.getElementById('txt-total-items').innerText = allDisplayData.length + ' Item';

                    const itemsPerPage = globalSettings.items_per_page || 9;
                    totalPages = Math.ceil(allDisplayData.length / itemsPerPage) || 1;
                    if (currentPage > totalPages) currentPage = 1;

                    renderGrid();
                    resetSlideTimer();
                }
            } catch (error) {
                console.error("Gagal memperbarui data monitor:", error);
            }
        }

        // RENDER GRID KARTU
        function renderGrid() {
            const container = document.getElementById('display-grid-container');
            const itemsPerPage = globalSettings.items_per_page || 9;

            if (allDisplayData.length === 0) {
                container.innerHTML = `
                    <div class="col-12 text-center py-5" style="grid-column: span 3;">
                        <i class="fa-solid fa-desktop text-white-50 display-3 mb-3"></i>
                        <h5 class="text-white">Belum Ada Material Ditampilkan</h5>
                        <p class="text-white-50 small">Silakan daftarkan material melalui menu Pengaturan Display Monitor.</p>
                    </div>`;
                return;
            }

            const startIndex = (currentPage - 1) * itemsPerPage;
            const pageItems = allDisplayData.slice(startIndex, startIndex + itemsPerPage);

            let html = '';
            pageItems.forEach(item => {
                let badgeClass = 'bg-secondary text-white';

                if (item.status_color === 'danger') {
                    badgeClass = 'bg-danger text-white';
                } else if (item.status_color === 'warning') {
                    badgeClass = 'bg-warning text-dark';
                } else if (item.status_color === 'success') {
                    badgeClass = 'bg-success text-white';
                }

                html += `
                    <div class="fade-in-content">
                        <div class="monitor-card">
                            <div>
                                <div class="mat-category mb-1">${escapeHtml(item.category)}</div>
                                <div class="mat-title" title="${escapeHtml(item.display_name)}">${escapeHtml(item.display_name)}</div>
                            </div>

                            <div class="d-flex justify-content-between align-items-end border-top border-white border-opacity-10 pt-2 my-1">
                                <div>
                                    <div class="stat-label mb-1">SISA STOK</div>
                                    <div class="stat-value-main">
                                        ${numberFormat(item.stok)}<span class="unit-text">${escapeHtml(item.satuan)}</span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="stat-label mb-1">DAYA TAHAN</div>
                                    <span class="badge ${badgeClass} badge-daya-tahan">
                                        ${escapeHtml(item.daya_tahan)}
                                    </span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center opacity-75 border-top border-white border-opacity-10 pt-1" style="font-size: 0.72rem;">
                                <span class="text-slate-400">Rate Usage:</span>
                                <span class="fw-bold text-white">${numberFormat(item.rate_usage)} ${escapeHtml(item.satuan)} / Bln</span>
                            </div>
                        </div>
                    </div>`;
            });

            container.innerHTML = html;
            document.getElementById('txt-page-indicator').innerText = `Halaman ${currentPage} dari ${totalPages}`;
        }

        // AUTO SLIDE ENGINE
        function resetSlideTimer() {
            if (slideTimer) clearInterval(slideTimer);

            if (globalSettings.auto_slide && totalPages > 1) {
                const intervalMs = (globalSettings.slide_interval || 10) * 1000;
                slideTimer = setInterval(() => {
                    currentPage = (currentPage >= totalPages) ? 1 : currentPage + 1;
                    renderGrid();
                }, intervalMs);
            }
        }

        // AUTO REFRESH COUNTDOWN ENGINE
        function startRefreshCountdown() {
            refreshCountdown = globalSettings.refresh_interval || 30;
            if (refreshTimer) clearInterval(refreshTimer);

            refreshTimer = setInterval(() => {
                refreshCountdown--;
                document.getElementById('txt-refresh-timer').innerText = refreshCountdown;

                if (refreshCountdown <= 0) {
                    fetchMonitorData();
                    refreshCountdown = globalSettings.refresh_interval || 30;
                }
            }, 1000);
        }

        // HELPER FUNCTIONS
        function escapeHtml(text) {
            if (!text) return '-';
            return String(text)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function numberFormat(val) {
            return new Intl.NumberFormat('id-ID').format(val || 0);
        }

        // TOGGLE MANUAL FULLSCREEN
        document.getElementById('btn-fullscreen-toggle')?.addEventListener('click', function() {
            if (!document.fullscreenElement) {
                requestNativeFullscreen();
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        });

        // INIT ON LOAD
        document.addEventListener('DOMContentLoaded', () => {
            fetchMonitorData();
            startRefreshCountdown();
        });
    </script>
</body>
</html>