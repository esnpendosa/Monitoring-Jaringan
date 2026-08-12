@php
use Illuminate\Support\Facades\Route;
@endphp
<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

    <!-- ! Hide app brand if navbar-full -->
    <div class="app-brand demo">
        <a href="{{url('/')}}" class="app-brand-link">
            <span class="app-brand-logo demo">
                <img src="{{ asset('assets/img/logo-umg.png') }}" alt="Logo UMG" width="40" height="40">
            </span>
            <span class="app-brand-text demo menu-text fw-bold ms-2">Rozitech</span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="icon-base bx bx-chevron-left icon-sm d-flex align-items-center justify-content-center"></i>
        </a>
    </div>

    <div class="menu-divider mt-0"></div>
    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        @foreach ($menuData[0]->menu as $index => $menu)
        @php
            $roleName = auth()->user()->role ? auth()->user()->role->name : 'Pelanggan';
            $isPelanggan = ($roleName === 'Pelanggan' || auth()->user()->id_role == 4);
            
            // Skip "Koneksi Saya" for non-customers (Admin, Manager, Technician)
            if (isset($menu->slug) && $menu->slug === 'my-connection' && !$isPelanggan) {
                continue;
            }

            // Skip "Absensi Pegawai" for customers
            if (isset($menu->slug) && $menu->slug === 'absensi' && $isPelanggan) {
                continue;
            }

            // Check if this is a header and if it should be skipped because all its items are hidden
            if (isset($menu->menuHeader)) {
                $hasVisibleItems = false;
                $lookaheadIndex = $index + 1;
                $menuItems = $menuData[0]->menu;
                
                while ($lookaheadIndex < count($menuItems)) {
                    $nextMenu = $menuItems[$lookaheadIndex];
                    
                    // If we reach another header, we stop
                    if (isset($nextMenu->menuHeader)) {
                        break;
                    }
                    
                    // Apply the same skip conditions for lookahead
                    $skipNext = false;
                    if (isset($nextMenu->slug)) {
                        if ($nextMenu->slug === 'my-connection' && !$isPelanggan) {
                            $skipNext = true;
                        }
                        if ($nextMenu->slug === 'absensi' && $isPelanggan) {
                            $skipNext = true;
                        }
                    }
                    
                    if (!$skipNext) {
                        if (!isset($nextMenu->permission) || (auth()->check() && auth()->user()->hasPermission($nextMenu->permission))) {
                            $hasVisibleItems = true;
                            break;
                        }
                    }
                    
                    $lookaheadIndex++;
                }
                
                if (!$hasVisibleItems) {
                    continue;
                }
            }
        @endphp
        @if (!isset($menu->permission) || (auth()->check() && auth()->user()->hasPermission($menu->permission)))

        {{-- adding active and open class if child is active --}}

        {{-- menu headers --}}
        @if (isset($menu->menuHeader))
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
        </li>
        @else

        {{-- active menu method --}}
        @php
        $activeClass = null;
        $currentRouteName = Route::currentRouteName();

        if ($currentRouteName === $menu->slug) {
        $activeClass = 'active';
        }
        elseif (isset($menu->submenu)) {
        if (gettype($menu->slug) === 'array') {
        foreach($menu->slug as $slug){
        if (str_contains($currentRouteName,$slug) and strpos($currentRouteName,$slug) === 0) {
        $activeClass = 'active open';
        }
        }
        }
        else{
        if (str_contains($currentRouteName,$menu->slug) and strpos($currentRouteName,$menu->slug) === 0) {
        $activeClass = 'active open';
        }
        }
        }
        @endphp

        {{-- main menu --}}
        <li class="menu-item {{$activeClass}}">
            <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}" class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
                @isset($menu->icon)
                <i class="{{ $menu->icon }}"></i>
                @endisset
                <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
                @isset($menu->badge)
                <div class="badge rounded-pill bg-{{ $menu->badge[0] }} text-uppercase ms-auto">{{ $menu->badge[1] }}</div>
                @endisset
            </a>

            {{-- submenu --}}
            @isset($menu->submenu)
            @include('layouts.sections.menu.submenu',['menu' => $menu->submenu])
            @endisset
        </li>
        @endif
        @endif
        @endforeach
    </ul>

</aside>
