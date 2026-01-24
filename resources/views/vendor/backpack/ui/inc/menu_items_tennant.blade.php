<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>
<x-backpack::menu-item title="Team Command Center" icon="la la-tachometer" :link="backpack_url('team-command-center')" />
<x-backpack::menu-item title="Workplace Presence" icon="la la-question" :link="backpack_url('workplace-presence')" />
<x-backpack::menu-item title="Presence events" icon="la la-question" :link="backpack_url('presence-event')" />
<x-backpack::menu-item title="Workplaces" icon="la la-question" :link="backpack_url('workplace')" />

<x-backpack::menu-dropdown title="Leave Management" icon="la la-calendar-check-o">
    <x-backpack::menu-dropdown-item title="Leave Requests" icon="la la-list" :link="backpack_url('leave-request')" />
    <x-backpack::menu-dropdown-item title="Leave Types" icon="la la-tags" :link="backpack_url('leave-type')" />
    <x-backpack::menu-dropdown-item title="Public Holidays" icon="la la-calendar" :link="backpack_url('public-holiday')" />
</x-backpack::menu-dropdown>

<x-backpack::menu-dropdown title="Authentication" icon="la la-puzzle-piece">
    <x-backpack::menu-dropdown-item title="Users" icon="la la-user" :link="backpack_url('user')" />
    <x-backpack::menu-dropdown-item title="Roles" icon="la la-group" :link="backpack_url('role')" />
    <x-backpack::menu-dropdown-item title="Permissions" icon="la la-key" :link="backpack_url('permission')" />
</x-backpack::menu-dropdown>
<x-backpack::menu-item title="Activity Logs" icon="la la-stream" :link="backpack_url('activity-log')" />
<x-backpack::menu-item title="Settings" icon="la la-cog" :link="backpack_url('setting')" />
