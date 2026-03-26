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
<nav class="module-tabs navbar navbar-expand border-bottom py-0" aria-label="Module switcher">
    <div class="{{ backpack_theme_config('options.useFluidContainers') ? 'container-fluid' : 'container-xxl' }}">
        <ul class="nav nav-tabs border-0 flex-nowrap" role="tablist">
            <li class="nav-item">
                <a class="nav-link {{ (!$isProductsModule && !$isAdminModule) ? 'active' : '' }}" href="{{ backpack_url('dashboard') }}">
                    <i class="la la-home me-1"></i>
                    {{ __('modules.condica') }}
                </a>
            </li>
            @can('view products')
            <li class="nav-item">
                <a class="nav-link {{ $isProductsModule ? 'active' : '' }}" href="{{ backpack_url('products') }}">
                    <i class="la la-cube me-1"></i>
                    Products
                </a>
            </li>
            @endcan
            <li class="nav-item">
                <a class="nav-link {{ $isAdminModule ? 'active' : '' }}" href="{{ backpack_url('admin-center') }}">
                    <i class="la la-cog me-1"></i>
                    Admin
                </a>
            </li>
        </ul>
    </div>
</nav>
<style>
    .module-tabs {
        background: #fff;
        z-index: 1000;
    }
    .module-tabs .nav-link {
        padding: 0.85rem 1.5rem;
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--tblr-body-color);
        opacity: 0.7;
        border: none;
        border-bottom: 3px solid transparent;
        border-radius: 0;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
    }
    .module-tabs .nav-link i {
        font-size: 1.1rem;
        transition: transform 0.2s ease;
    }
    .module-tabs .nav-link:hover {
        opacity: 1;
        color: var(--tblr-primary);
        background: rgba(var(--tblr-primary-rgb), 0.03);
    }
    .module-tabs .nav-link:hover i {
        transform: translateY(-1px);
    }
    .module-tabs .nav-link.active {
        opacity: 1;
        border-bottom-color: var(--tblr-primary);
        color: var(--tblr-primary);
        background: rgba(var(--tblr-primary-rgb), 0.05);
    }
</style>
@endif

