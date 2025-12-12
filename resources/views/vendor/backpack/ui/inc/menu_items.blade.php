@if(tenancy()->tenant)
@include('vendor.backpack.ui.inc.menu_items_tennant')
@else
@include('vendor.backpack.ui.inc.menu_items_central')
@endif
<x-backpack::menu-item title="Settings" icon="la la-cog" :link="backpack_url('setting')" />
<x-backpack::menu-item title="Workplaces" icon="la la-question" :link="backpack_url('workplace')" />
<x-backpack::menu-item title="Presence events" icon="la la-question" :link="backpack_url('presence-event')" />