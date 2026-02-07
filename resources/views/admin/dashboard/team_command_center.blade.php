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
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#attendanceModal">
                Foaia Colectivă de Prezență
            </button>
        </div>
    </div>

    <script>
        function exportAttendance(format) {
            const date = document.getElementById('attendanceMonth').value;
            if (!date) {
                alert('Please select a month');
                return;
            }
            window.location.href = '/admin/team-command-center/export?format=' + format + '&date=' + date + '-01';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1);
            const yyyy = lastMonth.getFullYear();
            const mm = String(lastMonth.getMonth() + 1).padStart(2, '0');

            const monthInput = document.getElementById('attendanceMonth');
            if (monthInput) {
                monthInput.value = `${yyyy}-${mm}`;
            }
        });
    </script>

    {{-- Stats Cards --}}
    <div class="row row-cards mb-4">
        @include(backpack_view('inc.widgets'), [ 'widgets' => app('widgets')->where('section', 'stats')->toArray() ])
    </div>

    <div class="row">
        {{-- Team Live Roster --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-0">
                    <h3 class="card-title">Team Live Roster</h3>
                </div>
                <div class="card-body p-0">
                    <x-bp-datatable
                        controller="App\Http\Controllers\Admin\TeamCommandCenterController"
                        :form-inside-card="false"
                        :use-fixed-header="false"
                    />
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
                        <strong>{{ $request->employee->name }}</strong> requested {{ $request->start_date->format('M jS') }} ({{ $request->leaveType->name }})
                    </div>
                    <div>
                        <a href="{{ backpack_url('leave-request/'.$request->id.'/edit') }}" class="btn btn-sm btn-success">Review</a>
                    </div>
                </div>
            </div>
            @endforeach

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

    <!-- Attendance Modal -->
    <div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="attendanceModalLabel">Export Foaia Colectivă de Prezență</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="attendanceMonth" class="form-label">Select Month</label>
              <input type="month" class="form-control" id="attendanceMonth">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-success" onclick="exportAttendance('excel')">Download Excel</button>
            <button type="button" class="btn btn-danger" onclick="exportAttendance('pdf')">Download Pdf</button>
          </div>
        </div>
      </div>
    </div>
</div>
@endsection
