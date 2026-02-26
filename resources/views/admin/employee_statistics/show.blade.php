@extends(backpack_view('blank'))

@php
  $default_breadrumbs = [
    backpack_user()->name => backpack_url('dashboard'),
    $crud->entity_name_plural => url($crud->route),
    'Vizualizare prezență' => false,
  ];

  // breadcrumbs can be defined in the Controller or here, as an array
  $breadcrumbs = $breadcrumbs ?? $default_breadrumbs;
@endphp

@section('header')
	<section class="container-fluid">
	  <h2>
        <span class="text-capitalize">{!! $crud->getHeading() ?? $crud->entity_name_plural !!}</span>
        <small>{!! $crud->getSubheading() ?? 'Vizualizare detaliată prezență.' !!}</small>

        @if ($crud->hasAccess('list'))
          <small><a href="{{ url($crud->route) . (request()->has('from_to') ? '?from_to='.request()->get('from_to') : '') }}" class="hidden-print font-sm"><i class="la la-angle-double-left"></i> Înapoi la listă</a></small>
        @endif
	  </h2>
	</section>
@endsection

@section('content')
<div class="row">
	<div class="{{ $crud->getShowContentClass() }}">

	<!-- Default box -->
	  <div class="">
	  	@if ($crud->model->track_view_count ?? false)
	  		<div class="card no-padding no-border mb-3">
	  			<div class="card-body">
	  				<i class="la la-eye"></i> {{ trans('backpack::crud.view_count') }}: {{ $entry->view_count }}
	  			</div>
	  		</div>
	  	@endif

        <div class="card no-padding no-border">
            <div class="card-header">
                <h3 class="card-title">
                    {{ $entry->name }} - {{ $startDate->format('d.m.Y') }} - {{ $endDate->format('d.m.Y') }}
                </h3>
            </div>
			<div class="card-body">
                <div class="presence-chart-container mb-4">
                    <div class="presence-chart-header d-flex text-muted small mb-2 border-bottom pb-1">
                        <div style="width: 100px;" class="shrink-0 font-weight-bold text-uppercase">Data</div>
                        <div class="flex-grow-1 position-relative" style="height: 20px;">
                            @for($h=0; $h<=24; $h+=2)
                                <div class="position-absolute text-center" style="left: {{ ($h/24)*100 }}%; width: 20px; transform: translateX(-50%);">
                                    {{ $h }}
                                </div>
                            @endfor
                        </div>
                        <div style="width: 60px;" class="text-right shrink-0 font-weight-bold text-uppercase">Total</div>
                    </div>

                    @php
                        $period = \Carbon\CarbonPeriod::create($startDate, $endDate);
                        $eventsByDay = $presenceEvents->groupBy(fn($e) => $e->start_at->format('Y-m-d'));
                    @endphp

                    @foreach($period as $date)
                        @php
                            $dayKey = $date->format('Y-m-d');
                            $dayEvents = $eventsByDay->get($dayKey, collect());
                            $totalMinutes = 0;
                        @endphp
                        <div class="d-flex align-items-center mb-1 py-1 border-bottom-light hover-bg-light">
                            <div style="width: 100px;" class="shrink-0 small font-weight-bold {{ $date->isWeekend() ? 'text-danger' : '' }}">
                                {{ $date->format('d.m') }} ({{ $date->minDayName }})
                            </div>
                            <div class="flex-grow-1 position-relative bg-light rounded-sm overflow-hidden border" style="height: 24px;">
                                <!-- Time Grid Lines -->
                                @for($h=1; $h<24; $h++)
                                    <div class="position-absolute h-100 border-right opacity-25" style="left: {{ ($h/24)*100 }}%; width: 1px; z-index: 1;"></div>
                                @endfor

                                <!-- Events -->
                                @foreach($dayEvents as $event)
                                    @php
                                        if (!$event->end_at) {
                                            $eventEnd = $date->isToday() ? now() : $event->start_at->copy()->endOfDay();
                                        } else {
                                            $eventEnd = $event->end_at;
                                        }
                                        
                                        // Ensure event doesn't go outside the current day for visualization
                                        $drawStart = $event->start_at->isSameDay($date) ? $event->start_at : $date->copy()->startOfDay();
                                        $drawEnd = $eventEnd->isSameDay($date) ? $eventEnd : $date->copy()->endOfDay();
                                        
                                        $startPercent = (($drawStart->hour * 60 + $drawStart->minute) / 1440) * 100;
                                        $endPercent = (($drawEnd->hour * 60 + $drawEnd->minute) / 1440) * 100;
                                        $widthPercent = $endPercent - $startPercent;
                                        
                                        if ($event->end_at) {
                                            $totalMinutes += $drawStart->diffInMinutes($drawEnd);
                                        }

                                        $colorClass = match($event->type) {
                                            'presence' => 'bg-success',
                                            'delegation' => 'bg-primary',
                                            'leave' => 'bg-warning',
                                            default => 'bg-secondary'
                                        };
                                        
                                        $title = $event->type . ': ' . $drawStart->format('H:i') . ' - ' . ($event->end_at ? $drawEnd->format('H:i') : '...');
                                    @endphp
                                    <div class="position-absolute h-100 {{ $colorClass }} shadow-sm" 
                                         style="left: {{ $startPercent }}%; width: {{ max(0.5, $widthPercent) }}%; z-index: 2;"
                                         data-toggle="tooltip"
                                         title="{{ $title }}">
                                    </div>
                                @endforeach
                            </div>
                            <div style="width: 60px;" class="text-right shrink-0 small font-mono">
                                @if($totalMinutes > 0)
                                    {{ sprintf('%d:%02d', floor($totalMinutes/60), $totalMinutes%60) }}
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="legend d-flex flex-row flex-wrap align-items-center mt-4 border-top pt-3">
                    <div class="d-flex align-items-center mr-4 mb-2">
                        <div class="bg-success rounded-sm mr-2" style="width: 16px; height: 16px;"></div>
                        <span class="small font-weight-bold">Prezență (Normal)</span>
                    </div>
                    <div class="d-flex align-items-center mr-4 mb-2">
                        <div class="bg-primary rounded-sm mr-2" style="width: 16px; height: 16px;"></div>
                        <span class="small font-weight-bold">Delegație</span>
                    </div>
                    <div class="d-flex align-items-center mr-4 mb-2">
                        <div class="bg-warning rounded-sm mr-2" style="width: 16px; height: 16px;"></div>
                        <span class="small font-weight-bold">Concediu / Invoire</span>
                    </div>
                </div>
			</div>
        </div>
	  </div>
	</div>
</div>
@endsection

@section('after_styles')
<style>
    .shrink-0 { flex-shrink: 0; }
    .border-bottom-light { border-bottom: 1px solid #f0f2f5; }
    .hover-bg-light:hover { background-color: #f8fafc; }
    .bg-light { background-color: #f1f5f9 !important; }
    .rounded-sm { border-radius: 4px; }
</style>
@endsection

@section('after_scripts')
<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    })
</script>
@endsection
