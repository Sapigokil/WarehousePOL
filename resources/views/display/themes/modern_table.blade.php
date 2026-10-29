<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIFASMAT - Modern Table Display Monitor</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --header-bg: #0f172a;
            --page-bg: #e2e8f0;
            --table-header-bg: #334155;
            --pill-qty-bg: #dbeafe;
            --pill-qty-text: #1e40af;
        }

        * { box-sizing: border-box; }

        html, body {
            height: 100vh;
            width: 100vw;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background-color: var(--page-bg);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #0f172a;
        }

        body {
            padding: 12px 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* OVERLAY FULLSCREEN PROMPT */
        #fullscreen-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(10px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* HEADER BRANDING */
        .monitor-header-box {
            background: var(--header-bg);
            border-radius: 12px;
            padding: 10px 20px;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.15);
        }

        .header-brand-title {
            font-size: 1.75rem;
            font-weight: 900;
            color: #ffffff;
            line-height: 1;
            letter-spacing: -0.5px;
        }

        .header-brand-subtitle {
            font-size: 0.82rem;
            color: #38bdf8;
            font-weight: 700;
            margin-top: 3px;
        }

        .header-date {
            font-size: 0.9rem;
            font-weight: 700;
            color: #cbd5e1;
        }

        .header-clock {
            font-size: 1.85rem;
            font-weight: 900;
            color: #ffffff;
            line-height: 1;
            letter-spacing: 1px;
        }

        /* SUMMARY METRICS CARDS (4 KOLOM) */
        .summary-card {
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.9);
            border-radius: 12px;
            padding: 10px 16px;
            height: 68px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
        }

        .summary-icon-box {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .summary-icon-box svg {
            width: 24px;
            height: 24px;
        }

        .summary-label {
            font-size: 0.72rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .summary-value {
            font-size: 1.55rem;
            font-weight: 900;
            line-height: 1;
            color: #0f172a;
        }

        .summary-unit {
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            margin-left: 2px;
        }

        /* 1 KOLOM TABLE CONTAINER WITH AUTO-SCROLL */
        .single-table-card {
            flex-grow: 1;
            margin: 10px 0;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #cbd5e1;
            height: calc(100vh - 175px);
        }

        .table-scroll-container {
            flex-grow: 1;
            overflow-y: auto;
            scroll-behavior: smooth;
        }

        .table-scroll-container::-webkit-scrollbar {
            width: 6px;
        }
        .table-scroll-container::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 4px;
        }

        .table-custom {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }

        .table-custom th {
            background: var(--table-header-bg);
            color: #ffffff;
            font-size: 0.85rem;
            font-weight: 800;
            text-transform: uppercase;
            padding: 12px 18px;
            letter-spacing: 0.5px;
            border: none;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .table-custom td {
            padding: 11px 18px;
            vertical-align: middle;
            font-size: 0.95rem;
            border-bottom: 1px solid #f1f5f9;
            color: #0f172a;
        }

        .table-custom tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .table-custom tbody tr:hover {
            background-color: #e0f2fe;
        }

        .col-no {
            font-weight: 900;
            color: #64748b;
            font-size: 1rem;
            text-align: center;
        }

        .mat-name-text {
            font-weight: 800;
            color: #0f172a;
            font-size: 1.05rem;
        }

        .badge-cat-tag {
            font-size: 0.7rem;
            font-weight: 800;
            background: #e2e8f0;
            color: #334155;
            padding: 3px 8px;
            border-radius: 4px;
            margin-bottom: 3px;
            display: inline-block;
            text-transform: uppercase;
        }

        .pill-qty {
            background: var(--pill-qty-bg);
            color: var(--pill-qty-text);
            font-size: 1.05rem;
            font-weight: 900;
            padding: 6px 14px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .pill-qty-unit {
            font-size: 0.8rem;
            font-weight: 700;
            color: #2563eb;
        }

        .text-daya-tahan {
            font-weight: 800;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .text-rate-usage {
            font-weight: 800;
            font-size: 0.95rem;
            color: #475569;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* FOOTER MONITOR BAR */
        .monitor-footer-bar {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 6px 18px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.78rem;
            font-weight: 700;
            color: #475569;
        }

        .fade-in-anim {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(3px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    {{-- OVERLAY PROMPT FULLSCREEN --}}
    <div id="fullscreen-overlay">
        <div class="text-center p-4 border border-info border-opacity-30 rounded-4 bg-white shadow-lg position-relative" style="max-width: 460px;">
            <div class="p-3 bg-primary text-white rounded-circle d-inline-block mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M1.5 1a.5.5 0 0 0-.5.5v3a.5.5 0 0 1-1 0v-3A1.5 1.5 0 0 1 1.5 0h3a.5.5 0 0 1 0 1zM11 .5a.5.5 0 0 1 .5-.5h3A1.5 1.5 0 0 1 16 1.5v3a.5.5 0 0 1-1 0v-3a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 1-.5-.5M.5 11a.5.5 0 0 1 .5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 1 0 1h-3A1.5 1.5 0 0 1 0 14.5v-3a.5.5 0 0 1 .5-.5m15 0a.5.5 0 0 1 .5.5v3a1.5 1.5 0 0 1-1.5 1.5h-3a.5.5 0 0 1 0-1h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 1 .5-.5"/>
                </svg>
            </div>
            <h4 class="text-dark fw-extrabold mb-1">DISPLAY MONITOR SIAP</h4>
            <p class="text-muted mb-4 small">
                Klik tombol di bawah untuk mengaktifkan antarmuka <strong class="text-primary">FULLSCREEN</strong>.
            </p>

            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-primary text-white fw-bold px-4 py-2 shadow-sm" id="btn-trigger-fullscreen">
                    Mode Fullscreen
                </button>
                <button type="button" class="btn btn-outline-secondary fw-semibold px-3 py-2" id="btn-cancel-overlay">
                    Batal
                </button>
            </div>
        </div>
    </div>

    {{-- HEADER BRANDING KONTRASTING --}}
    <div class="monitor-header-box d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary text-white p-2.5 rounded-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2zm3.564 1.428L5.838 5 8 5.865 13.846 3.5zM14.4 4.14 8 6.707V12.5a.5.5 0 0 1-.186.386l-4.5 3.5a.5.5 0 0 1-.628 0l-4.5-3.5A.5.5 0 0 1 2 12.5V6.707L8 9.3z"/>
                </svg>
            </div>
            <div>
                <div class="header-brand-title">SIFASMAT</div>
                <div class="header-brand-subtitle">Stock Monitoring & Command Center System</div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-4 text-end">
            <div>
                <div class="header-date" id="txt-live-date">--</div>
                <div class="header-clock" id="txt-live-clock">00:00:00</div>
            </div>
        </div>
    </div>

    {{-- SUMMARY CARDS METRICS BAR (4 KOLOM DENGAN ICON KONTRAS TINGGI) --}}
    <div class="row g-2 mb-2">
        {{-- 1. Total Material --}}
        <div class="col-md-3">
            <div class="summary-card">
                <div class="summary-icon-box bg-primary text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2zm3.564 1.428L5.838 5 8 5.865 13.846 3.5zM14.4 4.14 8 6.707V12.5a.5.5 0 0 1-.186.386l-4.5 3.5a.5.5 0 0 1-.628 0l-4.5-3.5A.5.5 0 0 1 2 12.5V6.707L8 9.3z"/>
                    </svg>
                </div>
                <div>
                    <div class="summary-label">Total Material</div>
                    <div class="summary-value"><span id="metric-total">0</span> <span class="summary-unit">Jenis</span></div>
                </div>
            </div>
        </div>

        {{-- 2. Stok Aman --}}
        <div class="col-md-3">
            <div class="summary-card">
                <div class="summary-icon-box bg-success text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l4.992-5.5a.75.75 0 0 0-.01-1.05z"/>
                    </svg>
                </div>
                <div>
                    <div class="summary-label text-success">Stok Aman</div>
                    <div class="summary-value text-success"><span id="metric-aman">0</span> <span class="summary-unit">Jenis</span></div>
                </div>
            </div>
        </div>

        {{-- 3. Stok Menipis --}}
        <div class="col-md-3">
            <div class="summary-card">
                <div class="summary-icon-box bg-warning text-dark">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                    </svg>
                </div>
                <div>
                    <div class="summary-label text-warning-emphasis">Stok Menipis</div>
                    <div class="summary-value text-warning-emphasis"><span id="metric-warning">0</span> <span class="summary-unit">Jenis</span></div>
                </div>
            </div>
        </div>

        {{-- 4. Stok Kritis --}}
        <div class="col-md-3">
            <div class="summary-card">
                <div class="summary-icon-box bg-danger text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="summary-label text-danger">Stok Kritis</div>
                    <div class="summary-value text-danger"><span id="metric-kritis">0</span> <span class="summary-unit">Jenis</span></div>
                </div>
            </div>
        </div>
    </div>

    {{-- 1 KOLOM TABLE AREA FULL-WIDTH --}}
    <div class="single-table-card">
        <div class="table-scroll-container" id="table-scroll-container">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th width="5%" class="text-center">No</th>
                        <th width="40%">Nama Material</th>
                        <th width="18%">QTY Stock</th>
                        <th width="18%">Daya Tahan</th>
                        <th width="19%">Rate Usage / Bulan</th>
                    </tr>
                </thead>
                <tbody id="tbody-material-list">
                    <tr><td colspan="5" class="text-center py-5 text-muted">Memuat data material...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- FOOTER MONITOR BAR --}}
    <div class="monitor-footer-bar">
        <div>
            SIFASMAT | Command Center Realtime Display System
        </div>
        <div class="d-flex align-items-center gap-3">
            <span>Status Server: <strong class="text-success">ONLINE</strong></span>
            <span>Auto Refresh: <strong id="txt-refresh-timer">--</strong>s</span>
            <span id="txt-item-counter">Menampilkan Semua Material</span>
            <button type="button" class="btn btn-sm btn-light border py-0 px-2" id="btn-fullscreen-toggle" style="font-size: 0.72rem;">
                Fullscreen
            </button>
        </div>
    </div>

    {{-- JAVASCRIPT MONITOR ENGINE & AUTO SCROLL --}}
    <script>
        const API_URL = "{{ route('display.api.data') }}";
        
        let allDisplayData = [];
        let globalSettings = {
            refresh_interval: 30
        };

        let refreshCountdown = 30;
        let refreshTimer = null;
        let autoScrollInterval = null;

        // FULLSCREEN PROMPT LOGIC
        const fsOverlay = document.getElementById('fullscreen-overlay');
        const btnTriggerFs = document.getElementById('btn-trigger-fullscreen');
        const btnCancelFs = document.getElementById('btn-cancel-overlay');

        function requestNativeFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().then(() => {
                    if (fsOverlay) fsOverlay.style.display = 'none';
                }).catch(() => {
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

        // JAM & TANGGAL DIGITAL REALTIME
        function updateClock() {
            const now = new Date();
            const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const timeStr = now.toLocaleTimeString('id-ID', { hour12: false });
            
            document.getElementById('txt-live-date').innerText = now.toLocaleDateString('id-ID', dateOptions);
            document.getElementById('txt-live-clock').innerText = timeStr;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // FETCH DATA MONITOR VIA API
        async function fetchMonitorData() {
            try {
                const response = await fetch(API_URL);
                const result = await response.json();

                if (result) {
                    globalSettings = result.settings;
                    allDisplayData = result.data || [];

                    calculateMetrics(allDisplayData);
                    renderTable();
                    initAutoScroll();
                }
            } catch (error) {
                console.error("Gagal memperbarui data monitor:", error);
            }
        }

        // KALKULASI METRICS SUMMARY
        function calculateMetrics(data) {
            document.getElementById('metric-total').innerText = data.length;
            
            let aman = 0, warning = 0, kritis = 0;
            data.forEach(item => {
                if (item.status_color === 'danger') kritis++;
                else if (item.status_color === 'warning') warning++;
                else aman++;
            });

            document.getElementById('metric-aman').innerText = aman;
            document.getElementById('metric-warning').innerText = warning;
            document.getElementById('metric-kritis').innerText = kritis;
        }

        // RENDER TABEL 1 KOLOM FULL-WIDTH
        function renderTable() {
            const tbody = document.getElementById('tbody-material-list');

            if (allDisplayData.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center py-5 text-muted">Belum ada material terdaftar untuk tampilan monitor.</td></tr>`;
                return;
            }

            let html = '';
            allDisplayData.forEach((item, idx) => {
                let dayaTahanColor = 'text-success';
                if (item.status_color === 'danger') {
                    dayaTahanColor = 'text-danger fw-extrabold';
                } else if (item.status_color === 'warning') {
                    dayaTahanColor = 'text-warning-emphasis fw-bold';
                }

                html += `
                    <tr class="fade-in-anim">
                        <td class="col-no">${idx + 1}</td>
                        <td>
                            <span class="badge-cat-tag">${escapeHtml(item.category)}</span>
                            <div class="mat-name-text">${escapeHtml(item.display_name)}</div>
                        </td>
                        <td>
                            <span class="pill-qty">
                                ${numberFormat(item.stok)} <span class="pill-qty-unit">${escapeHtml(item.satuan)}</span>
                            </span>
                        </td>
                        <td>
                            <span class="text-daya-tahan ${dayaTahanColor}">
                                ${escapeHtml(item.daya_tahan)}
                            </span>
                        </td>
                        <td>
                            <span class="text-rate-usage">
                                ${numberFormat(item.rate_usage)} ${escapeHtml(item.satuan)}
                            </span>
                        </td>
                    </tr>`;
            });

            tbody.innerHTML = html;
            document.getElementById('txt-item-counter').innerText = `Menampilkan Semua (${allDisplayData.length} Item)`;
        }

        // AUTO-SCROLL ENGINE CONTINUOUS LOOP
        function initAutoScroll() {
            const container = document.getElementById('table-scroll-container');
            if (!container) return;

            if (autoScrollInterval) clearInterval(autoScrollInterval);

            const scrollSpeed = 1;      
            const stepInterval = 40;    
            const pauseDuration = 3000; 
            let isPaused = false;

            autoScrollInterval = setInterval(() => {
                if (isPaused) return;

                const maxScroll = container.scrollHeight - container.clientHeight;
                if (maxScroll <= 10) return; 

                if (container.scrollTop >= maxScroll - 2) {
                    isPaused = true;
                    setTimeout(() => {
                        container.scrollTo({ top: 0, behavior: 'smooth' });
                        setTimeout(() => {
                            isPaused = false;
                        }, pauseDuration);
                    }, pauseDuration);
                } else {
                    container.scrollTop += scrollSpeed;
                }
            }, stepInterval);
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