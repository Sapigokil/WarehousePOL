<header id="navbar">
    <div class="d-flex align-items-center">
        <a href="#" class="navbar-icon" id="toggle-sidebar">
            <i class="fa-solid fa-bars"></i>
        </a>
        <h5 class="mb-0 text-dark fw-bold">@yield('title', 'Dashboard')</h5>
    </div>
    
    <div class="d-flex align-items-center">
        <a href="#" class="navbar-icon position-relative">
            <i class="fa-regular fa-bell"></i>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                3
            </span>
        </a>

        <div class="dropdown ms-3">
            <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=01a9ac&color=fff" alt="User" class="rounded-circle shadow-sm" width="38" height="38">
                <div class="ms-2 d-none d-md-block text-dark">
                    <div class="fw-bold" style="font-size: 0.9rem;">{{ Auth::user()->name }}</div>
                    <div class="text-muted" style="font-size: 0.75rem;">{{ Auth::user()->role ?? 'User' }}</div>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow" aria-labelledby="userDropdown">
                <li><a class="dropdown-item py-2" href="#"><i class="fa-solid fa-user me-2 text-muted"></i> Profil Saya</a></li>
                @can('Setting Menu')
                <li><a class="dropdown-item py-2" href="#"><i class="fa-solid fa-gear me-2 text-muted"></i> Pengaturan</a></li>
                @endcan
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item py-2 text-danger fw-bold">
                            <i class="fa-solid fa-power-off me-2"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>