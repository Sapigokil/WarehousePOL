<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SIFASMAT - @yield('title', 'Dashboard')</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    @php
        // Mengambil tema dari database secara dinamis
        $activeTheme = \App\Models\Setting::where('key', 'app_theme')->value('value') ?? 'light-blue';
    @endphp

    <style>
        /* =========================================
           SISTEM TEMA DINAMIS (CSS VARIABLES)
           ========================================= */
        
        :root {
            --sidebar-bg: #23272a;
            --sidebar-brand-bg: #1e2124;
            --sidebar-hover-bg: #353f54;
            --sidebar-text: #b0b3b8;
            --sidebar-text-hover: #ffffff;
            --sidebar-border: #333333;
            
            --primary-color: #01a9ac;
            --primary-hover: #01898c;
            --primary-gradient-1: #01a9ac;
            --primary-gradient-2: #01898c;
            
            --layout-bg: #e4e9f0;
        }

        body.theme-navy-blue {
            --sidebar-bg: #0f172a;
            --sidebar-brand-bg: #020617;
            --sidebar-hover-bg: #1e293b;
            --sidebar-text: #94a3b8;
            --sidebar-text-hover: #ffffff;
            --sidebar-border: #1e293b;

            --primary-color: #1e40af;
            --primary-hover: #1d4ed8;
            --primary-gradient-1: #1e40af;
            --primary-gradient-2: #1d4ed8;
            
            --layout-bg: #f0f4f8; 
        }

        body.theme-modern-blue {
            --sidebar-bg: #1e1b4b;
            --sidebar-brand-bg: #11102e;
            --sidebar-hover-bg: #2e2970;
            --sidebar-text: #a5b4fc;
            --sidebar-text-hover: #ffffff;
            --sidebar-border: #2e2970;

            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --primary-gradient-1: #4f46e5;
            --primary-gradient-2: #4338ca;
            
            --layout-bg: #f5f7fa;
        }

        body.theme-ocean-blue {
            --sidebar-bg: #0f172a; 
            --sidebar-brand-bg: #020617;
            --sidebar-hover-bg: #1e293b;
            --sidebar-text: #94a3b8;
            --sidebar-text-hover: #ffffff;
            --sidebar-border: #1e293b;

            --primary-color: #0284c7;
            --primary-hover: #0369a1;
            --primary-gradient-1: #0284c7;
            --primary-gradient-2: #0369a1;
            
            --layout-bg: #f1f5f9;
        }

        body.theme-light-blue {
            --sidebar-bg: #0c4a6e;
            --sidebar-brand-bg: #082f49;
            --sidebar-hover-bg: #075985;
            --sidebar-text: #bae6fd;
            --sidebar-text-hover: #ffffff;
            --sidebar-border: #075985;

            --primary-color: #0ea5e9;
            --primary-hover: #0284c7;
            --primary-gradient-1: #0ea5e9;
            --primary-gradient-2: #0284c7;
            
            --layout-bg: #f0f9ff;
        }

        /* =========================================
           KELAS BANTUAN GLOBAL (UTILITY CLASSES)
           ========================================= */
        .text-theme { color: var(--primary-color) !important; }
        .bg-theme { background-color: var(--primary-color) !important; }
        .btn-theme { 
            background-color: var(--primary-color) !important; 
            color: #ffffff !important; 
            border: none; 
        }
        .btn-theme:hover { background-color: var(--primary-hover) !important; }
        
        .header-banner-theme {
            background: linear-gradient(135deg, var(--primary-gradient-1) 0%, var(--primary-gradient-2) 100%);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        /* =========================================
           PENGATURAN LAYOUT UTAMA
           ========================================= */
        body {
            font-family: 'Open Sans', sans-serif;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
            display: flex;
        }

        #sidebar {
            width: 260px;
            background-color: var(--sidebar-bg); 
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1030;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.2);
            transition: background-color 0.3s ease;
        }
        
        #sidebar::-webkit-scrollbar { width: 6px; }
        #sidebar::-webkit-scrollbar-track { background: transparent; }
        #sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }

        .sidebar-brand {
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--sidebar-brand-bg);
            color: var(--sidebar-text-hover);
            font-size: 1.25rem;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: 1px;
            border-bottom: 1px solid var(--sidebar-border);
            transition: background-color 0.3s ease;
        }

        .sidebar-brand:hover { color: var(--primary-color); }

        .sidebar-menu {
            padding: 10px 0; 
            list-style: none;
            margin: 0;
        }

        .sidebar-menu-header {
            padding: 8px 25px; 
            font-size: 0.7rem; 
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-top: 5px; 
            margin-bottom: 2px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 8px 25px; 
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 0.85rem; 
            border-left: 4px solid transparent;
            transition: all 0.2s ease-in-out;
        }

        .sidebar-link i {
            width: 26px; 
            font-size: 1rem; 
            text-align: left;
        }

        .sidebar-link:hover, .sidebar-link.active {
            color: var(--sidebar-text-hover);
            background-color: var(--sidebar-hover-bg);
            border-left: 4px solid var(--primary-color);
        }

        .sidebar-link.active { font-weight: 600; }

        #right-wrapper {
            margin-left: 260px;
            width: calc(100% - 260px);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: var(--layout-bg) !important; 
            transition: background-color 0.3s ease;
        }

        #navbar {
            height: 70px;
            background-color: #ffffff;
            box-shadow: 0 1px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            padding: 0 25px;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .navbar-icon {
            color: #555;
            font-size: 1.2rem;
            cursor: pointer;
            text-decoration: none;
            margin-right: 20px;
            transition: color 0.2s;
        }

        .navbar-icon:hover { color: var(--primary-color); }

        #main-content {
            flex: 1; 
            padding: 30px;
        }

        #footer {
            background-color: #ffffff;
            border-top: 1px solid #e9ecef;
            padding: 15px 30px;
            color: #6c757d;
            font-size: 0.85rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 20px 0 rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }
        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #f1f1f1;
            padding: 18px 25px;
            font-weight: 700;
            color: #333;
            border-radius: 8px 8px 0 0 !important;
        }
        .card-body { padding: 25px; }
    </style>
    @stack('styles')
</head>
<body class="theme-{{ $activeTheme }}">

    @include('partials.sidebar')

    <div id="right-wrapper">
        
        @include('partials.navbar')

        <div id="main-content">
            @yield('content')
        </div>

        @include('partials.footer')

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>