@extends(backpack_view('blank'))

@section('content')
<div class="container-fluid dashboard-employee p-0">
    {{-- Row 1: Hero and Alerts --}}
    <div class="row">
        {{-- Hero: Monthly Progress --}}
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="card-title mb-0">Monthly Goal</h3>
                        <span class="badge {{ $metrics['overtime_hours'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                            {{ $metrics['overtime_hours'] >= 0 ? '+' : '' }}{{ $metrics['overtime_hours'] }}h Overtime
                        </span>
                    </div>

                    <div class="d-flex align-items-end mb-2">
                        <h1 class="mb-0 me-2">{{ $metrics['logged_hours'] }}</h1>
                        <span class="text-muted mb-1">/ {{ $metrics['expected_hours_month'] }} hrs</span>
                    </div>

                    <div class="progress progress-lg position-relative" style="height: 24px;">
                        {{-- Logged Hours --}}
                        <div class="progress-bar {{ $metrics['month_progress_pct'] >= $metrics['target_progress_pct'] ? 'bg-success' : 'bg-primary' }}"
                             role="progressbar"
                             style="width: {{ $metrics['month_progress_pct'] }}%"
                             aria-valuenow="{{ $metrics['month_progress_pct'] }}"
                             aria-valuemin="0"
                             aria-valuemax="100">
                        </div>

                        {{-- Ghost Bar (Target) --}}
                         <div class="position-absolute"
                             style="left: {{ $metrics['target_progress_pct'] }}%; top: 0; bottom: 0; width: 2px; background-color: rgba(0,0,0,0.5); z-index: 10;"
                             title="Target: {{ round($metrics['target_progress_pct']) }}%">
                        </div>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mt-1">
                        <span>0%</span>
                        <span>Target: {{ round($metrics['target_progress_pct']) }}%</span>
                        <span>100%</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Alerts --}}
        <div class="col-lg-4">
            <div class="mb-4">
                <h3 class="mb-3">Action Required</h3>
                @forelse($alerts as $alert)
                    <div class="card mb-2 bg-{{ $alert->type == 'danger' ? 'danger-lt' : ($alert->type == 'warning' ? 'warning-lt' : 'secondary-lt') }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="alert-title mb-1">{{ $alert->title }}</h4>
                                    <div class="text-muted">{{ $alert->message }}</div>
                                </div>
                                <div class="ms-2">
                                    <a href="{{ $alert->actionUrl }}" class="btn btn-sm btn-{{ $alert->type == 'danger' ? 'danger' : ($alert->type == 'warning' ? 'warning' : 'primary') }}">
                                        {{ $alert->actionLabel }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="card">
                        <div class="card-body text-center text-muted">
                            <i class="la la-check-circle fs-2 text-success"></i>
                            <p class="mb-0 mt-2">All caught up! No alerts.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Row 2: Recent Activity and Upcoming --}}
    <div class="row">
        {{-- Recent Activity Snapshot --}}
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Recent Activity</h3>
                    <div class="card-actions">
                        <a href="{{ backpack_url('presence-event') }}" class="btn btn-sm btn-outline-primary">View Full History</a>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Shift</th>
                                <th>Location</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activity_log as $day)
                            <tr>
                                <td>
                                    <div class="font-weight-bold">{{ $day['date']->format('D, M j') }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-blue-lt">
                                        {{ $day['start_time'] }} — {{ $day['end_time'] }}
                                    </span>
                                </td>
                                <td>
                                    @if($day['is_remote'])
                                        <span class="text-purple" title="{{ $day['location_name'] }}"><i class="ti ti-home"></i> Remote</span>
                                    @else
                                        <span class="text-muted" title="{{ $day['location_name'] }}"><i class="ti ti-building"></i> Office</span>
                                    @endif
                                </td>
                                <td class="text-muted">
                                    {{ $day['hours_str'] }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No recent activity found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Upcoming Widget --}}
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Upcoming</h3>
                </div>
                <div class="list-group list-group-flush">
                    @if($next_leave)
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar bg-green-lt">🌴</span>
                            </div>
                            <div class="col text-truncate">
                                <a href="#" class="text-body d-block">Next Leave (Approved)</a>
                                <div class="text-muted text-truncate mt-n1">
                                    {{ $next_leave->start_date->format('M j') }} - {{ $next_leave->end_date->format('M j') }}
                                    ({{ $next_leave->total_days }} days)
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($next_holiday)
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar bg-yellow-lt">📅</span>
                            </div>
                            <div class="col text-truncate">
                                <a href="#" class="text-body d-block">Next Holiday</a>
                                <div class="text-muted text-truncate mt-n1">
                                    {{ $next_holiday->description }} ({{ $next_holiday->date->format('M j') }})
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if(!$next_leave && !$next_holiday)
                    <div class="list-group-item text-center text-muted py-4">
                        Nothing upcoming. <a href="{{ backpack_url('leave-request/create') }}">Plan a break?</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
