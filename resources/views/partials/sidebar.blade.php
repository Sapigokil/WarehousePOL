<nav id="sidebar">
    <a href="{{ route('dashboard') }}" class="sidebar-brand text-theme">
        <i class="fa-solid fa-boxes-stacked me-2"></i> INVENTORY
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
                <a href="{{ route('inbound.index') }}" class="sidebar-link">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Barang Masuk
                </a>
            </li>
            @endcan

            @can('Warehouse Menu')
            <li>
                <a href="{{ route('stocks.index') }}" class="sidebar-link {{ request()->routeIs('stocks.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-layer-group"></i> Stok Gudang
                </a>
            </li>
            @endcan

            @can('Outbound Menu')
            <li>
                <a href="{{ route('outbounds.index') }}" class="sidebar-link {{ request()->routeIs('outbounds.*') ? 'active' : '' }}">
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
                    <a href="{{ route('tracking.index') }}" class="sidebar-link {{ request()->routeIs('tracking.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-magnifying-glass-location"></i> Lacak Nomor Seri
                    </a>
                </li>
                
                <li class="nav-item mb-1">
                    <a class="sidebar-link {{ request()->routeIs('reports.*') ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="#reportMenu" role="button" aria-expanded="{{ request()->routeIs('reports.*') ? 'true' : 'false' }}" aria-controls="reportMenu">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div><i class="fa-solid fa-chart-pie"></i> Laporan & Alert</div>
                            <i class="fa-solid fa-chevron-down" style="font-size: 0.75rem;"></i>
                        </div>
                    </a>
                    <div class="collapse {{ request()->routeIs('reports.*') ? 'show' : '' }}" id="reportMenu">
                        <ul style="list-style: none; padding-left: 20px; margin-top: 2px; margin-bottom: 2px;">
                            <li class="mb-1">
                                <a href="{{ route('reports.mutation') }}" class="sidebar-link {{ request()->routeIs('reports.mutation') ? 'active' : '' }}" style="padding: 6px 25px; border-left: none; font-size: 0.8rem;">
                                    <i class="fa-solid fa-chart-bar me-2" style="width: 20px; text-align: center; font-size: 0.85rem;"></i> Mutasi Stock
                                </a>
                            </li>
                            <li class="mb-1">
                                <a href="{{ route('reports.inbound') }}" class="sidebar-link {{ request()->routeIs('reports.inbound') ? 'active' : '' }}" style="padding: 6px 25px; border-left: none; font-size: 0.8rem;">
                                    <i class="fa-solid fa-arrow-right-to-bracket me-2" style="width: 20px; text-align: center; font-size: 0.85rem;"></i> Riwayat Penerimaan
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('reports.outbound') }}" class="sidebar-link {{ request()->routeIs('reports.outbound') ? 'active' : '' }}" style="padding: 6px 25px; border-left: none; font-size: 0.8rem;">
                                    <i class="fa-solid fa-arrow-right-from-bracket me-2" style="width: 20px; text-align: center; font-size: 0.85rem;"></i> Riwayat Distribusi
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
        @endcan

        @canany(['Setting Menu', 'User Menu', 'Warehouse Menu'])
        <li class="sidebar-menu-header">Sistem</li>

            @can('User Menu')        
            <li class="nav-item mb-1">
                <a class="sidebar-link {{ request()->is('users*') || request()->is('roles*') ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="#userMenu" role="button" aria-expanded="{{ request()->is('users*') || request()->is('roles*') ? 'true' : 'false' }}" aria-controls="userMenu">
                    <div class="d-flex w-100 justify-content-between align-items-center">
                        <div><i class="fa-solid fa-users-gear"></i> Manajemen User</div>
                        <i class="fa-solid fa-chevron-down" style="font-size: 0.75rem;"></i>
                    </div>
                </a>
                <div class="collapse {{ request()->is('users*') || request()->is('roles*') ? 'show' : '' }}" id="userMenu">
                    <ul style="list-style: none; padding-left: 20px; margin-top: 2px; margin-bottom: 2px;">
                        <li class="mb-1">
                            <a href="{{ route('users.index') }}" class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}" style="padding: 6px 25px; border-left: none; font-size: 0.8rem;">
                                <i class="fa-solid fa-users me-2" style="width: 20px; text-align: center; font-size: 0.85rem;"></i> Daftar Pengguna
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('roles.index') }}" class="sidebar-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" style="padding: 6px 25px; border-left: none; font-size: 0.8rem;">
                                <i class="fa-solid fa-user-shield me-2" style="width: 20px; text-align: center; font-size: 0.85rem;"></i> Role & Permission
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            @endcan
            
            @can('Setting Menu')
            <li>
                <a href="{{ route('sislogs.index') }}" class="sidebar-link">
                    <i class="fa-solid fa-clipboard-list"></i> System Logs
                </a>
            </li>
            <li>
                <a href="{{ route('settings.index') }}" class="sidebar-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-sliders"></i> Pengaturan Global
                </a>
            </li>
            <li class="nav-item mb-1">
                <a class="sidebar-link {{ request()->is('categories*') || request()->is('warehouses*') || request()->is('materials*') ? '' : 'collapsed' }}" data-bs-toggle="collapse" href="#warehouseSettingMenu" role="button" aria-expanded="{{ request()->is('categories*') || request()->is('warehouses*') || request()->is('materials*') ? 'true' : 'false' }}" aria-controls="warehouseSettingMenu">
                    <div class="d-flex w-100 justify-content-between align-items-center">
                        <div><i class="fa-solid fa-gear"></i> Pengaturan Warehouse</div>
                        <i class="fa-solid fa-chevron-down" style="font-size: 0.8rem;"></i>
                    </div>
                </a>
                <div class="collapse {{ request()->is('categories*') || request()->is('warehouses*') || request()->is('materials*') ? 'show' : '' }}" id="warehouseSettingMenu">
                    <ul style="list-style: none; padding-left: 20px; margin-top: 5px; margin-bottom: 5px;">
                        <li class="mb-1">
                            <a href="{{ route('materials.index') }}" class="sidebar-link {{ request()->routeIs('materials.*') ? 'active' : '' }}" style="padding: 6px 25px; border-left: none; font-size: 0.8rem;">
                                <i class="fa-solid fa-box me-2" style="width: 20px; text-align: center; font-size: 0.85rem;"></i> Daftar Barang
                            </a>
                        </li>
                        <li class="mb-1">
                            <a href="{{ route('categories.index') }}" class="sidebar-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" style="padding: 6px 25px; border-left: none; font-size: 0.8rem;">
                                <i class="fa-solid fa-tags me-2" style="width: 20px; text-align: center; font-size: 0.85rem;"></i> Kategori Materiel
                            </a>
                        </li>
                        <li class="mb-1">
                            <a href="{{ route('warehouses.index') }}" class="sidebar-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}" style="padding: 6px 25px; border-left: none; font-size: 0.8rem;">
                                <i class="fa-solid fa-warehouse me-2" style="width: 20px; text-align: center; font-size: 0.85rem;"></i> Daftar Gudang
                            </a>
                        </li>
                        <li class="mb-1">
                            <a href="{{ route('destinations.index') }}" class="sidebar-link {{ request()->routeIs('destinations.*') ? 'active' : '' }}" style="padding: 6px 25px; border-left: none; font-size: 0.8rem;">
                                <i class="fa-solid fa-map-location-dot me-2" style="width: 20px; text-align: center; font-size: 0.85rem;"></i> Daftar Penerima
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            @endcan
            
        @endcanany
    </ul>
</nav>