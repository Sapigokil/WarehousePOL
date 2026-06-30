<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesi Berakhir</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #525b68; /* Sesuai tema login slate grey */
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background-color: #23272a; 
            color: #ffffff; 
            border: 1px solid #444;
            box-shadow: 0 5px 30px rgba(0,0,0,0.8);
        }
    </style>
</head>
<body>

    <div class="modal fade" id="kickedModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title text-warning fw-bold">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>Sesi Telah Berakhir
                    </h5>
                </div>
                <div class="modal-body border-0">
                    <p class="mb-2">Ada pengguna lain yang telah masuk menggunakan akun Anda di perangkat atau <i>browser</i> yang berbeda.</p>
                    <p class="mb-0 text-secondary small">Sistem hanya mengizinkan satu sesi aktif demi keamanan data dan integritas log. Silakan klik OK untuk kembali ke halaman Login.</p>
                </div>
                <div class="modal-footer border-0">
                    <a href="{{ url('/login') }}" class="btn fw-bold w-100" style="background-color: #01a9ac; color: white;">OK, KEMBALI KE LOGIN</a>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Paksa modal langsung muncul saat halaman dimuat
        document.addEventListener("DOMContentLoaded", function() {
            var myModal = new bootstrap.Modal(document.getElementById('kickedModal'));
            myModal.show();
        });
    </script>
</body>
</html>