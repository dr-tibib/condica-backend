@if(tenancy()->tenant)
@include('vendor.backpack.ui.inc.menu_items_tennant')
@else
@include('vendor.backpack.ui.inc.menu_items_central')
@endif