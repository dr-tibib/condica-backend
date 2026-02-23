<x-backpack::menu-item title="{{ trans('backpack::base.dashboard') }}" icon="la la-home" :link="backpack_url('dashboard')" />

<x-backpack::menu-separator title="{{ __('central.infrastructure') }}" />
<x-backpack::menu-item title="{{ __('central.tenants') }}" icon="la la-building" :link="backpack_url('tenant')" />
<x-backpack::menu-item title="{{ __('central.domains') }}" icon="la la-globe" :link="backpack_url('domain')" />

<x-backpack::menu-separator title="{{ __('central.security') }}" />
<x-backpack::menu-dropdown title="{{ __('central.access_control') }}" icon="la la-shield">
    <x-backpack::menu-dropdown-item title="{{ __('central.central_users') }}" icon="la la-user" :link="backpack_url('user')" />
    <x-backpack::menu-dropdown-item title="{{ __('central.roles') }}" icon="la la-group" :link="backpack_url('role')" />
    <x-backpack::menu-dropdown-item title="{{ __('central.permissions') }}" icon="la la-key" :link="backpack_url('permission')" />
</x-backpack::menu-dropdown>

<x-backpack::menu-separator title="{{ __('central.logs') }}" />
<x-backpack::menu-item title="{{ __('central.activity_logs') }}" icon="la la-stream" :link="backpack_url('activity-log')" />
