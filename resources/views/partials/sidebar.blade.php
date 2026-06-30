<nav id="sidebar">
    <a href="{{ route('dashboard') }}" class="sidebar-brand">
        <i class="fa-solid fa-boxes-stacked me-2" style="color: #01a9ac;"></i> INVENTORY
    </a>
    
    <ul class="sidebar-menu">
        
        @can('Dashboard Menu')
        <li class="sidebar-menu-header">Utama</li>
        <li>
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="#" class="sidebar-link text-warning">
                <i class="fa-solid fa-display"></i> Monitor Ruangan
            </a>
        </li>
        @endcan

        @canany(['Warehouse Menu', 'Inbound Menu', 'Outbound Menu'])
        <li class="sidebar-menu-header">Operasional</li>
        
            @can('Inbound Menu')
            <li>
                <a href="#" class="sidebar-link">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Barang Masuk
                </a>
            </li>
            @endcan

            @can('Warehouse Menu')
            <li>
                <a href="#" class="sidebar-link">
                    <i class="fa-solid fa-warehouse"></i> Master Warehouse
                </a>
            </li>
            @endcan

            @can('Outbound Menu')
            <li>
                <a href="#" class="sidebar-link">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i> Barang Keluar
                </a>
            </li>
            <li>
                <a href="#" class="sidebar-link">
                    <i class="fa-solid fa-truck-fast"></i> Distribusi
                </a>
            </li>
            @endcan
        @endcanany

        @can('Report Menu')
        <li class="sidebar-menu-header">Analitik</li>
        <li>
            <a href="#" class="sidebar-link">
                <i class="fa-solid fa-magnifying-glass-location"></i> Tracking Seri
            </a>
        </li>
        <li>
            <a href="#" class="sidebar-link">
                <i class="fa-solid fa-chart-pie"></i> Laporan & Alert
            </a>
        </li>
        @endcan

        @canany(['Setting Menu', 'User Menu'])
        <li class="sidebar-menu-header">Sistem</li>

            @can('User Menu')        
            <li class="nav-item mb-1">
                <a class="sidebar-link {{ request()->is('users*') || request()->is('roles*') ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="#userMenu" role="button" aria-expanded="{{ request()->is('users*') || request()->is('roles*') ? 'true' : 'false' }}" aria-controls="userMenu">
                    <div class="d-flex w-100 justify-content-between align-items-center">
                        <div><i class="fa-solid fa-users-gear"></i> Manajemen User</div>
                        <i class="fa-solid fa-chevron-down" style="font-size: 0.8rem;"></i>
                    </div>
                </a>
                <div class="collapse {{ request()->is('users*') || request()->is('roles*') ? 'show' : '' }}" id="userMenu">
                    <ul style="list-style: none; padding-left: 20px; margin-top: 5px; margin-bottom: 5px;">
                        <li class="mb-1">
                            <a href="{{ route('users.index') }}" class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}" style="padding: 8px 25px; border-left: none;">
                                <i class="fa-solid fa-angle-right" style="width: 15px;"></i> Daftar Pengguna
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('roles.index') }}" class="sidebar-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" style="padding: 8px 25px; border-left: none;">
                                <i class="fa-solid fa-angle-right" style="width: 15px;"></i> Role & Permission
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            @endcan
            
            @can('Setting Menu')
            <li>
                <a href="#" class="sidebar-link">
                    <i class="fa-solid fa-clipboard-list"></i> System Logs
                </a>
            </li>
            <li>
                <a href="#" class="sidebar-link">
                    <i class="fa-solid fa-sliders"></i> Pengaturan Global
                </a>
            </li>
            @endcan
            
        @endcanany
    </ul>
</nav>