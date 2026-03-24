@php
    $prefix = trim(config('backpack.base.route_prefix', 'admin'), '/');

    $isProductsModule = request()->routeIs('backpack.products*')
        || request()->is($prefix.'/products', $prefix.'/products/*');
    $isAdminModule = request()->is(
        $prefix.'/admin-center*',
        $prefix.'/edit-account-info*',
        $prefix.'/change-password*',
        $prefix.'/user*',
        $prefix.'/role*',
        $prefix.'/permission*',
        $prefix.'/activity-log*',
        $prefix.'/setting*',
    );
@endphp
@if(backpack_auth()->check())
<nav class="module-tabs navbar navbar-expand border-bottom" aria-label="Module switcher">
    <div class="{{ backpack_theme_config('options.useFluidContainers') ? 'container-fluid' : 'container-xxl' }}">
        <ul class="nav nav-tabs border-0 flex-nowrap" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ (!$isProductsModule && !$isAdminModule) ? 'active' : '' }}" href="{{ backpack_url('dashboard') }}">
                    {{ __('modules.condica') }}
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $isProductsModule ? 'active' : '' }}" href="{{ backpack_url('products') }}">
                    Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ $isAdminModule ? 'active' : '' }}" href="{{ backpack_url('admin-center') }}">
                    Admin
                </a>
            </li>
        </ul>
    </div>
</nav>
<style>
    .module-tabs .nav-link {
        padding: 0.6rem 1.25rem;
        font-weight: 500;
        color: var(--tblr-body-color);
        opacity: 0.85;
        border: none;
        border-bottom: 3px solid transparent;
        border-radius: 0;
    }
    .module-tabs .nav-link:hover {
        opacity: 1;
    }
    .module-tabs .nav-link.active {
        opacity: 1;
        border-bottom-color: var(--tblr-primary);
        color: var(--tblr-primary);
    }
</style>
@endif

