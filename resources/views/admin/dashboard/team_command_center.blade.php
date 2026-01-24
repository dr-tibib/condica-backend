@extends(backpack_view('blank'))

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h2 mb-0">Team Command Center</h1>
            <p class="text-muted">Real-time visibility into team presence and tasks.</p>
        </div>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm w-auto d-inline-block">
                <option>Engineering Dept</option>
                <option>All Departments</option>
            </select>
            <input type="date" class="form-control form-control-sm w-auto d-inline-block" value="{{ \Carbon\Carbon::today()->format('Y-m-d') }}">
            <button class="btn btn-sm btn-primary">Export Report</button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row row-cards mb-4">
        {{-- Currently On Shift --}}
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-start-3 border-start-success">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="subheader">Currently On Shift</div>
                    </div>
                    <div class="h1 mb-1">{{ $stats['on_shift'] }}</div>
                    <div class="text-success small">Active Now</div>
                </div>
            </div>
        </div>
        {{-- On Delegation --}}
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-start-3 border-start-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="subheader">On Delegation</div>
                    </div>
                    <div class="h1 mb-1">{{ $stats['on_delegation'] }}</div>
                    <div class="text-primary small">Off-site work</div>
                </div>
            </div>
        </div>
        {{-- Absent / Late --}}
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-start-3 border-start-danger">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="subheader">Absent / Late</div>
                    </div>
                    <div class="h1 mb-1">{{ $stats['absent'] }}</div>
                    <div class="text-danger small">Requires Attention</div>
                </div>
            </div>
        </div>
        {{-- Upcoming Time-Off --}}
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-start-3 border-start-warning">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="subheader">Upcoming Time-Off</div>
                    </div>
                    <div class="h1 mb-1">{{ $stats['upcoming_leave'] }}</div>
                    <div class="text-muted small">Next 7 Days</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Team Live Roster --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-0">
                    <h3 class="card-title">Team Live Roster</h3>
                    <div class="card-actions">
                        <form action="" method="GET">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search employee..." value="{{ request('search') }}">
                        </form>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Live Status</th>
                                <th>Location</th>
                                <th>Shift</th>
                                <th>Actual Hours</th>
                                <th>Trend (7D)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($roster as $item)
                            <tr>
                                <td>
                                    <div class="d-flex py-1 align-items-center">
                                        {{-- Avatar --}}
                                        <span class="avatar me-2" style="background-image: url({{ $item['user']->avatar_url ?? '' }})">
                                            {{ substr($item['user']->name, 0, 1) }}
                                        </span>
                                        <div class="flex-fill">
                                            <div class="font-weight-medium">{{ $item['user']->name }}</div>
                                            <div class="text-muted"><a href="#" class="text-reset">{{ $item['user']->department->name ?? 'No Dept' }}</a></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $item['status_class'] }}-lt">● {{ $item['status'] }}</span>
                                </td>
                                <td>
                                    {{ $item['location'] }}
                                </td>
                                <td class="text-muted">
                                    {{ $item['shift'] }}
                                </td>
                                <td>
                                    {{ $item['actual_hours'] }}
                                </td>
                                <td class="text-{{ $item['trend_class'] }}">
                                    {{ $item['trend'] }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No employees found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Action Center --}}
        <div class="col-lg-4">
            <h3 class="mb-3">Action Center</h3>

            {{-- Time Off Requests --}}
            @foreach($actions['time_off_requests'] as $request)
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-warning font-weight-bold">TIME OFF REQUEST</small>
                        <small class="text-muted">{{ $request->created_at->diffForHumans() }}</small>
                    </div>
                    <div class="mb-2">
                        <strong>{{ $request->user->name }}</strong> requested {{ $request->start_date->format('M jS') }} ({{ $request->leaveType->name }})
                    </div>
                    <div>
                        <a href="{{ backpack_url('leave-request/'.$request->id.'/edit') }}" class="btn btn-sm btn-success">Review</a>
                        {{-- <button class="btn btn-sm btn-outline-secondary">Deny</button> --}}
                    </div>
                </div>
            </div>
            @endforeach

            {{-- Delegation Requests (Mock/Placeholder) --}}
            {{--
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-primary font-weight-bold">DELEGATION REQUEST</small>
                        <small class="text-muted">5h ago</small>
                    </div>
                    <div class="mb-2">
                        <strong>John D.</strong> wants to delegate tasks to <strong>Mike R.</strong>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-success">Approve</button>
                        <button class="btn btn-sm btn-outline-secondary">Review</button>
                    </div>
                </div>
            </div>
            --}}

            {{-- Alerts --}}
            @if($actions['understaffed'])
            <div class="alert alert-danger" role="alert">
                <div class="d-flex">
                    <div>
                        <h4 class="alert-title"><i class="la la-warning"></i> Understaffed Alert</h4>
                        <div class="text-muted">Only {{ $actions['current_staff'] }} staff for the rush (Target: {{ $actions['target_staff'] }}).</div>
                    </div>
                </div>
            </div>
            @endif

            @if($actions['overtime_count'] > 0)
            <div class="alert alert-info" role="alert">
                <div class="d-flex">
                    <div>
                        <h4 class="alert-title"><i class="la la-info-circle"></i> Overtime Warning</h4>
                        <div class="text-muted">{{ $actions['overtime_count'] }} employees approaching limits.</div>
                    </div>
                </div>
            </div>
            @endif

            @if($actions['time_off_requests']->isEmpty() && !$actions['understaffed'] && $actions['overtime_count'] == 0)
            <div class="card">
                <div class="card-body text-center text-muted">
                    <i class="la la-check-circle fs-2 text-success"></i>
                    <p class="mb-0 mt-2">All caught up! No actions required.</p>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection
