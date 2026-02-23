<div class="w-100 justify-content-center d-none d-lg-flex sidebar-shortcuts mb-2">
    <ul class="nav d-flex align-items-center justify-content-center gap-3">
        @if(backpack_theme_config('options.showColorModeSwitcher'))
        <li class="nav-item">
            @include(backpack_view('layouts.partials.switch_theme'))
        </li>
        @endif
        @include(backpack_view('inc.topbar_right_content'))
    </ul>
    <style>
        /* Reduce spacing around the brand/logo */
        .navbar-brand {
            margin-bottom: 0.25rem !important;
            padding-top: 0.75rem !important;
            padding-bottom: 0.75rem !important;
        }

        /* Adjust shortcuts container */
        .sidebar-shortcuts {
            border-top: 1px solid rgba(var(--tblr-border-color-rgb), 0.08);
            border-bottom: 1px solid rgba(var(--tblr-border-color-rgb), 0.08);
            padding: 0.5rem 0;
            background: rgba(var(--tblr-bg-surface-rgb), 0.02);
        }

        /* Hide text in shortcuts when in sidebar */
        .sidebar-shortcuts .nav-link div.d-none.d-xl-block {
            display: none !important;
        }

        /* Center and align items */
        .sidebar-shortcuts .nav-item,
        .sidebar-shortcuts .dropdown {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 24px;
        }

        .sidebar-shortcuts .nav-item+.nav-item::before,
        .sidebar-shortcuts .nav-item+.dropdown::before,
        .sidebar-shortcuts .dropdown+.nav-item::before,
        .sidebar-shortcuts .dropdown+.dropdown::before {
            content: "";
            width: 1px;
            height: 16px;
            background: var(--tblr-border-color);
            margin: 0 0.5rem;
            opacity: 0.5;
        }

        /* Ensure flags look good in sidebar */
        .sidebar-shortcuts .avatar-xs svg {
            filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.05));
            border-radius: 2px;
        }

        /* Reduce menu top padding */
        #mobile-menu .navbar-nav {
            padding-top: 0.25rem !important;
        }

        /* Sidebar item hover effect for shortcuts */
        .sidebar-shortcuts .nav-link:hover {
            opacity: 0.8;
            transform: scale(1.05);
            transition: all 0.2s ease;
        }
    </style>
</div>