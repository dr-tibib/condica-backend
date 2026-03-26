@php
    $prefix = trim(config('backpack.base.route_prefix', 'admin'), '/');

    $isAuthOpen = request()->is(
        $prefix.'/edit-account-info*',
        $prefix.'/change-password*',
        $prefix.'/user*',
        $prefix.'/role*',
        $prefix.'/permission*',
    );

    $isSystemOpen = request()->is(
        $prefix.'/activity-log*',
        $prefix.'/setting*',
        $prefix.'/tenant*',
    );
@endphp
<x-backpack::menu-item title="Admin dashboard" icon="la la-home" :link="backpack_url('admin-center')" />
<x-backpack::menu-separator title="Administration" />
@if(backpack_user()->hasRole('superadmin') || backpack_user()->is_global_superadmin)
    <x-backpack::menu-item title="Tenants" icon="la la-building" :link="backpack_url('tenant')" />
@endif

<x-backpack::menu-dropdown title="Authentication" icon="la la-lock" :open="$isAuthOpen">
    <x-backpack::menu-dropdown-item
        title="My account"
        icon="la la-id-badge"
        :link="backpack_url('edit-account-info')"
        class="{{ request()->is($prefix.'/edit-account-info*', $prefix.'/change-password*') ? 'active' : '' }}"
    />
    <x-backpack::menu-dropdown-item
        title="Users"
        icon="la la-user"
        :link="backpack_url('user')"
        class="{{ request()->is($prefix.'/user*') ? 'active' : '' }}"
    />
    <x-backpack::menu-dropdown-item
        title="Roles"
        icon="la la-group"
        :link="backpack_url('role')"
        class="{{ request()->is($prefix.'/role*') ? 'active' : '' }}"
    />
    <x-backpack::menu-dropdown-item
        title="Permissions"
        icon="la la-key"
        :link="backpack_url('permission')"
        class="{{ request()->is($prefix.'/permission*') ? 'active' : '' }}"
    />
</x-backpack::menu-dropdown>

<x-backpack::menu-dropdown title="System" icon="la la-cogs" :open="$isSystemOpen">
    <x-backpack::menu-dropdown-item
        title="Activity Logs"
        icon="la la-stream"
        :link="backpack_url('activity-log')"
        class="{{ request()->is($prefix.'/activity-log*') ? 'active' : '' }}"
    />
    <x-backpack::menu-dropdown-item
        title="Settings"
        icon="la la-cog"
        :link="backpack_url('setting')"
        class="{{ request()->is($prefix.'/setting*') ? 'active' : '' }}"
    />
</x-backpack::menu-dropdown>

