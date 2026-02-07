<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="related-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="presence-tab" data-bs-toggle="tab" href="#presence" role="tab" aria-controls="presence" aria-selected="true">Presence Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="delegations-tab" data-bs-toggle="tab" href="#delegations" role="tab" aria-controls="delegations" aria-selected="false">Delegations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="leaves-tab" data-bs-toggle="tab" href="#leaves" role="tab" aria-controls="leaves" aria-selected="false">Leave Requests</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="related-tabs-content">
                    <div class="tab-pane fade show active" id="presence" role="tabpanel" aria-labelledby="presence-tab">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Workplace</th>
                                        <th>Method</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($entry->presenceEvents()->latest()->take(20)->get() as $event)
                                    <tr>
                                        <td>{{ $event->event_time }}</td>
                                        <td>{{ $event->event_type }}</td>
                                        <td>{{ $event->workplace->name ?? '-' }}</td>
                                        <td>{{ $event->method }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No presence events found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2 text-end">
                             <a href="{{ backpack_url('presence-event') }}?employee_id={{ $entry->id }}" class="btn btn-sm btn-secondary">View All</a>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="delegations" role="tabpanel" aria-labelledby="delegations-tab">
                         <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Workplace</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($entry->delegations()->latest()->take(20)->get() as $delegation)
                                    <tr>
                                        <td>{{ $delegation->start_date }}</td>
                                        <td>{{ $delegation->end_date }}</td>
                                        <td>{{ $delegation->workplace->name ?? '-' }}</td>
                                        <td>{{ $delegation->status ?? '-' }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No delegations found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                         <div class="mt-2 text-end">
                             <a href="{{ backpack_url('delegation') }}?employee_id={{ $entry->id }}" class="btn btn-sm btn-secondary">View All</a>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="leaves" role="tabpanel" aria-labelledby="leaves-tab">
                         <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($entry->leaveRequests()->latest()->take(20)->get() as $leave)
                                    <tr>
                                        <td>{{ $leave->start_date }}</td>
                                        <td>{{ $leave->end_date }}</td>
                                        <td>{{ $leave->leaveType->name ?? '-' }}</td>
                                        <td>{{ $leave->status }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No leave requests found.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                         <div class="mt-2 text-end">
                             <a href="{{ backpack_url('leave-request') }}?employee_id={{ $entry->id }}" class="btn btn-sm btn-secondary">View All</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Enable Bootstrap tabs if not already handled
    // Backpack usually loads Bootstrap JS, so data-bs-toggle should work.
</script>
