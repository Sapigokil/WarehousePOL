<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Warehouse Inventory - @yield('title', 'Dashboard')</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            overflow-x: hidden;
            margin: 0;
            padding: 0;
            display: flex;
        }

        /* 1. Sidebar Component */
        #sidebar {
            width: 260px;
            background-color: #23272a; /* Dark grey konsisten dengan tema login */
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1030;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.2);
        }
        
        /* Custom Scrollbar untuk Sidebar */
        #sidebar::-webkit-scrollbar { width: 6px; }
        #sidebar::-webkit-scrollbar-track { background: transparent; }
        #sidebar::-webkit-scrollbar-thumb { background: #555; border-radius: 4px; }

        .sidebar-brand {
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #1e2124;
            color: #ffffff;
            font-size: 1.25rem;
            font-weight: 700;
            text-decoration: none;
            letter-spacing: 1px;
            border-bottom: 1px solid #333;
        }

        .sidebar-brand:hover { color: #01a9ac; }

        .sidebar-menu {
            padding: 15px 0;
            list-style: none;
            margin: 0;
        }

        .sidebar-menu-header {
            padding: 10px 25px;
            font-size: 0.75rem;
            color: #888;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-top: 10px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: #b0b3b8;
            text-decoration: none;
            font-size: 0.95rem;
            border-left: 4px solid transparent;
            transition: all 0.2s ease-in-out;
        }

        .sidebar-link i {
            width: 30px;
            font-size: 1.1rem;
            text-align: left;
        }

        .sidebar-link:hover, .sidebar-link.active {
            color: #ffffff;
            background-color: #353f54;
            border-left: 4px solid #01a9ac;
        }

        .sidebar-link.active { font-weight: 600; }

        /* Area Kanan (Navbar + Konten + Footer) */
        #right-wrapper {
            margin-left: 260px;
            width: calc(100% - 260px);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: #e4e9f0 !important; /* TAMBAHKAN BARIS INI */
        }

        /* 2. Navbar Component */
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

        .navbar-icon:hover { color: #01a9ac; }

        /* 3. View Display Component */
        #main-content {
            flex: 1; /* Membuat konten mengisi ruang kosong antara navbar dan footer */
            padding: 30px;
        }

        /* 4. Footer Component */
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

        /* Global Card Styling */
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
<body>

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