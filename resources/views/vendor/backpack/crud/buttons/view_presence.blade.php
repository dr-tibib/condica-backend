@if ($crud->hasAccess('show'))
	<a href="{{ url($crud->route.'/'.$entry->getKey().'/show') . (request()->has('from_to') ? '?from_to='.request()->get('from_to') : '') }}" class="btn btn-sm btn-link"><i class="la la-eye"></i> Vizualizează</a>
@endif
