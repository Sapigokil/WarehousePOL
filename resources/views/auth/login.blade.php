<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Inventory</title>
    
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
        
        /* 1. TEMA MASTER: DARK (Default Tosca) */
        :root {
            --primary-color: #01a9ac;
            --primary-hover: #01898c;
            --primary-gradient-1: #01a9ac;
            --primary-gradient-2: #01898c;
            
            --login-bg-1: #23272a; 
            --login-bg-2: #1e2124;
            --layout-bg: #e4e9f0; 
            
            /* Sidebar */
            --sidebar-bg: #23272a;
            --sidebar-brand-bg: #1e2124;
            --sidebar-hover-bg: #353f54;
            --sidebar-text: #b0b3b8;
            --sidebar-text-hover: #ffffff;
            --sidebar-border: #333333;
        }

        /* 2. TEMA PRESET: NAVY BLUE */
        body.theme-navy-blue {
            --primary-color: #1e40af;
            --primary-hover: #1d4ed8;
            --primary-gradient-1: #1e40af;
            --primary-gradient-2: #1d4ed8;
            
            --login-bg-1: #1e3a8a;
            --login-bg-2: #0f172a;
            --layout-bg: #f0f4f8; 
            
            /* Sidebar */
            --sidebar-bg: #0f172a;
            --sidebar-brand-bg: #020617;
            --sidebar-hover-bg: #1e293b;
            --sidebar-text: #94a3b8;
            --sidebar-text-hover: #ffffff;
            --sidebar-border: #1e293b;
        }

        /* 3. TEMA PRESET: MODERN BLUE */
        body.theme-modern-blue {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --primary-gradient-1: #4f46e5;
            --primary-gradient-2: #4338ca;
            
            --login-bg-1: #3730a3;
            --login-bg-2: #1e1b4b;
            --layout-bg: #f5f7fa;
            
            /* Sidebar */
            --sidebar-bg: #1e1b4b;
            --sidebar-brand-bg: #11102e;
            --sidebar-hover-bg: #2e2970;
            --sidebar-text: #a5b4fc;
            --sidebar-text-hover: #ffffff;
            --sidebar-border: #2e2970;
        }

        /* 4. TEMA PRESET: OCEAN BLUE */
        body.theme-ocean-blue {
            --primary-color: #0284c7;
            --primary-hover: #0369a1;
            --primary-gradient-1: #0284c7;
            --primary-gradient-2: #0369a1;
            
            --login-bg-1: #0369a1;
            --login-bg-2: #0f172a;
            --layout-bg: #f1f5f9;
            
            /* Sidebar */
            --sidebar-bg: #0f172a; 
            --sidebar-brand-bg: #020617;
            --sidebar-hover-bg: #1e293b;
            --sidebar-text: #94a3b8;
            --sidebar-text-hover: #ffffff;
            --sidebar-border: #1e293b;
        }

        /* 5. TEMA PRESET: LIGHT BLUE */
        body.theme-light-blue {
            --primary-color: #0ea5e9;
            --primary-hover: #0284c7;
            --primary-gradient-1: #0ea5e9;
            --primary-gradient-2: #0284c7;
            
            --login-bg-1: #38bdf8; 
            --login-bg-2: #0369a1;
            --layout-bg: #f0f9ff;
            
            /* Sidebar */
            --sidebar-bg: #0c4a6e;
            --sidebar-brand-bg: #082f49;
            --sidebar-hover-bg: #075985;
            --sidebar-text: #bae6fd;
            --sidebar-text-hover: #ffffff;
            --sidebar-border: #075985;
        }

        /* =========================================
           STYLING HALAMAN LOGIN
           ========================================= */
        body {
            font-family: 'Open Sans', sans-serif;
            overflow-x: hidden;
            background-color: var(--layout-bg);
            transition: background-color 0.3s ease;
        }
        
        /* 1. Dekorasi CSS Kiri */
        .bg-login-left {
            background: linear-gradient(135deg, var(--login-bg-1) 0%, var(--login-bg-2) 100%);
            position: relative;
            overflow: hidden;
            transition: background 0.3s ease;
        }
        
        /* Ornamen transparan pelengkap background */
        .glass-shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 24px;
            transform: rotate(15deg);
        }
        .shape-1 { width: 300px; height: 300px; top: -50px; left: -100px; }
        .shape-2 { width: 150px; height: 150px; bottom: 15%; left: 10%; background: rgba(0, 0, 0, 0.05); transform: rotate(35deg); }
        .shape-3 { width: 400px; height: 400px; bottom: -150px; right: -100px; transform: rotate(45deg); }
        .shape-4 { width: 80px; height: 80px; top: 20%; right: 15%; background: rgba(255, 255, 255, 0.04); }

        /* 2. Styling Area Kanan (Form) */
        .login-card {
            width: 100%;
            max-width: 420px;
            padding: 40px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.03); 
            border: 1px solid #f1f1f1;
        }
        
        /* Kolom Input Form */
        .form-control, .input-group-text {
            background-color: var(--layout-bg);
            border: none;
            padding: 12px 15px;
            transition: background-color 0.3s ease;
        }
        .form-control:focus {
            background-color: #ffffff;
            border: 1px solid var(--primary-color);
            box-shadow: none;
        }
        
        /* Tombol Login */
        .btn-login {
            background-color: var(--primary-color);
            color: white;
            padding: 12px;
            border-radius: 8px;
            transition: 0.3s;
            border: none;
        }
        .btn-login:hover {
            background-color: var(--primary-hover);
            color: white;
        }
    </style>
</head>
<body class="theme-{{ $activeTheme }}">
    <div class="container-fluid vh-100 p-0">
        <div class="row g-0 h-100">
            
            <div class="col-lg-6 d-none d-lg-flex bg-login-left align-items-center justify-content-center">
                
                <div class="glass-shape shape-1"></div>
                <div class="glass-shape shape-2"></div>
                <div class="glass-shape shape-3"></div>
                <div class="glass-shape shape-4"></div>

                <div class="text-center text-white position-relative" style="z-index: 10;">
                    @php
                        $logoFiles = glob(public_path('images/logos/logo*.*'));
                        $logoUrls = [];
                        
                        if ($logoFiles) {
                            foreach ($logoFiles as $file) {
                                $logoUrls[] = asset('images/logos/' . basename($file));
                            }
                        }
                    @endphp

                    @if(count($logoUrls) > 0)
                        <div class="d-flex justify-content-center align-items-center gap-4 mb-4">
                            @foreach($logoUrls as $url)
                                <img src="{{ $url }}" alt="Logo Instansi" style="height: 410px; object-fit: contain;">
                            @endforeach
                        </div>
                    @endif

                    <h4 class="fw-bold mb-2" style="letter-spacing: 1px;">SISTEM INFORMASI<br>TATA KELOLA ADMINISTRASI FASILITAS & MATERIAL SBST</h4>
                    <p class="mb-0 text-white-50">&copy; {{ date('Y') }}</p>
                </div>
            </div>

            <div class="col-lg-6 d-flex align-items-center justify-content-center">
                <div class="login-card">
                    <h5 class="fw-bold mb-4" style="color: #444;">Selamat Datang di Sistem</h5>
                    
                    @if($errors->any())
                        <div class="alert alert-danger py-2 px-3 small border-0 shadow-sm">
                            Username atau Password Anda salah.
                        </div>
                    @endif

                    <form action="{{ route('login') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label class="form-label text-muted fw-semibold" style="font-size: 0.85rem;">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="Masukan Username Anda" required autofocus>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-muted fw-semibold" style="font-size: 0.85rem;">Password</label>
                            <div class="input-group">
                                <input type="password" name="password" id="password" class="form-control border-end-0" placeholder="Masukan Password Anda" required>
                                <span class="input-group-text border-start-0" onclick="togglePassword()" style="cursor: pointer;">
                                    <i class="fa-solid fa-eye text-muted" id="eye-icon"></i>
                                </span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input type="checkbox" name="remember" class="form-check-input shadow-sm" id="rememberMe">
                                <label class="form-check-label text-muted" for="rememberMe" style="font-size: 0.85rem;">Ingat Saya</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-login w-100 fw-bold">Masuk</button>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>