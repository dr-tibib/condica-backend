<!DOCTYPE html>

<html lang="{{ app()->getLocale() }}" dir="{{ backpack_theme_config('html_direction') }}">

<head>
    @include(backpack_view('inc.head'))
</head>

<body class="{{ backpack_theme_config('classes.body') }}" bp-layout="vertical">

@include(backpack_view('layouts.partials.light_dark_mode_logic'))

@include(backpack_view('layouts.partials.module_tabs'))

<div class="page">
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

    @if($isProductsModule)
        @include(backpack_view('layouts._vertical.products_menu_container'))
    @elseif($isAdminModule)
        @include(backpack_view('layouts._vertical.admin_menu_container'))
    @else
        @include(backpack_view('layouts._vertical.menu_container'))
    @endif

    <div class="page-wrapper">

        <div class="page-body">
            <main class="{{ backpack_theme_config('options.useFluidContainers') ? 'container-fluid' : 'container-xxl' }}">

                @yield('before_breadcrumbs_widgets')
                @includeWhen(isset($breadcrumbs), backpack_view('inc.breadcrumbs'))
                @yield('after_breadcrumbs_widgets')
                @yield('header')

                <div class="container-fluid animated fadeIn">
                    @yield('before_content_widgets')
                    @yield('content')
                    @yield('after_content_widgets')
                </div>
            </main>
        </div>

        @include(backpack_view('inc.footer'))
    </div>
</div>

@yield('before_scripts')
@stack('before_scripts')

@include(backpack_view('inc.scripts'))
@include(backpack_view('inc.theme_scripts'))

@yield('after_scripts')
@stack('after_scripts')
</body>
</html>

