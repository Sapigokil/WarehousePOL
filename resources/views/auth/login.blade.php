<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Warehouse Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            /* ========================================================
               PILIHAN KECERAHAN BACKGROUND ABU-ABU 
               Silakan aktifkan salah satu yang paling sesuai selera.
               ======================================================== */
            
            /* Pilihan A: Slate Grey (Abu-abu cerah elegan, kontras sangat baik) */
            background-color: #525b68; 

            /* Pilihan B: Neutral Medium Grey (Abu-abu pertengahan) */
            /* background-color: #737f8d; */

            /* Pilihan C: Light Silver Grey (Abu-abu sangat cerah) */
            /* background-color: #b0b3b8; */

            color: #ffffff;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Open Sans', sans-serif;
        }
        
        .card-login {
            background-color: #23272a; /* Warna kartu tetap dipertahankan gelap agar menonjol */
            border: 1px solid #444;
            border-radius: 8px;
            box-shadow: 0 4px 25px rgba(0,0,0,0.6); /* Sedikit menambah area bayangan */
            width: 100%;
            max-width: 400px;
        }
        
        .form-control {
            background-color: #333333;
            border: 1px solid #555555;
            color: #ffffff;
        }
        
        .form-control:focus {
            background-color: #40444b;
            border-color: #01a9ac; /* Aksen hijau/biru teal untuk fokus */
            color: #ffffff;
            box-shadow: none;
        }
        
        .form-control::placeholder {
            color: #888888;
        }
        
        .btn-login {
            background-color: #01a9ac;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            background-color: #018d8f;
        }
    </style>
</head>
<body>
    <div class="card-login p-4">
        <div class="text-center mb-4">
            <h4 class="mb-1 fw-bold">WAREHOUSE SYSTEM</h4>
            <p class="text-secondary small">Silakan login untuk mengakses sistem</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger py-2 border-0" style="background-color: #ed4245; color: white;" role="alert">
                <small>{{ $errors->first() }}</small>
            </div>
        @endif

        <form method="POST" action="{{ url('/login') }}">
            @csrf
            <div class="mb-3">
                <label for="username" class="form-label small text-secondary">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="{{ old('username') }}" placeholder="Masukkan username" required autofocus autocomplete="username">
            </div>
            <div class="mb-4">
                <label for="password" class="form-label small text-secondary">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn btn-login w-100 text-white fw-bold py-2">LOGIN</button>
        </form>
    </div>
</body>
</html>