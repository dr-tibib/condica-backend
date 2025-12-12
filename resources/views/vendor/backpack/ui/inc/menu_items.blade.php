@if(tenancy()->tenant)
@include('vendor.backpack.ui.inc.menu_items_tennant')
@else
@include('vendor.backpack.ui.inc.menu_items_central')
@endif
<x-backpack::menu-item title="Settings" icon="la la-cog" :link="backpack_url('setting')" />