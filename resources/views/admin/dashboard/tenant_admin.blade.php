@extends(backpack_view('blank'))

@section('after_styles')
<style>
    .ai-insights-content p { margin-bottom: 0.5rem; }
    .ai-insights-content ul { padding-left: 1.25rem; }
    .ai-insights-content h1, .ai-insights-content h2, .ai-insights-content h3 {
        font-size: 1rem; font-weight: 600; margin-top: 0.75rem; margin-bottom: 0.25rem;
    }
    .ai-loading {
        display: flex; align-items: center; gap: 0.5rem; color: #6c757d; padding: 1rem 0;
    }
    .ai-spinner {
        width: 1.25rem; height: 1.25rem;
        border: 2px solid #dee2e6; border-top-color: #206bc4;
        border-radius: 50%; animation: spin 0.8s linear infinite; flex-shrink: 0;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>
@endsection

@section('content')
<div class="container-fluid p-0">

    {{-- A. Employee Statistics Banner --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <div class="fs-4 fw-bold">📊 Statistici Angajați &amp; Condica</div>
                        <div class="text-white-50">Vizualizează rapoarte detaliate și descarcă Condica de prezență</div>
                    </div>
                    <a href="{{ backpack_url('employee-statistics') }}" class="btn btn-light btn-lg">
                        Vezi Statistici →
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- B. Today's Snapshot --}}
    @php
        $absent = max(0, $todayStats['total'] - $todayStats['present'] - $todayStats['on_leave'] - $todayStats['on_delegation']);
    @endphp
    <div class="row mb-4">
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-green fs-1 fw-bold">{{ $todayStats['present'] }}</div>
                    <div class="text-muted">✓ Prezenți azi</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-azure fs-1 fw-bold">{{ $todayStats['on_leave'] }}</div>
                    <div class="text-muted">🌴 În Concediu</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="text-blue fs-1 fw-bold">{{ $todayStats['on_delegation'] }}</div>
                    <div class="text-muted">✈ În Delegație</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="{{ $absent > 0 ? 'text-red' : 'text-muted' }} fs-1 fw-bold">{{ $absent }}</div>
                    <div class="text-muted">⚠ Absenți</div>
                </div>
            </div>
        </div>
    </div>

    {{-- C. Main Content Row --}}
    <div class="row mb-4">
        {{-- AI Insights --}}
        <div class="col-lg-8 mb-4 mb-lg-0">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">🤖 Analiză Lunară AI — {{ $monthName }} {{ $year }}</h3>
                    <button id="ai-refresh-btn" class="btn btn-sm btn-outline-secondary" onclick="loadAiInsights(true)">
                        Actualizează
                    </button>
                </div>
                <div class="card-body" id="ai-insights-body">
                    <div class="ai-loading" id="ai-loading-state">
                        <div class="ai-spinner"></div>
                        <span>Se generează analiza AI...</span>
                    </div>
                    <div class="ai-insights-content d-none" id="ai-insights-content"></div>
                    <div class="text-danger d-none" id="ai-error-state">
                        <small>⚠ Analiza AI nu a putut fi încărcată. <a href="#" onclick="loadAiInsights(false); return false;">Încearcă din nou</a></small>
                    </div>
                </div>
                <div class="card-footer text-muted small d-none" id="ai-footer">
                    Generat de AI · Actualizat la <span id="ai-cached-at"></span>
                </div>
            </div>
        </div>

        {{-- Issues Summary --}}
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0">Probleme Pontaj</h3>
                    @if($issues['total_issues'] > 0)
                        <span class="badge bg-red">{{ $issues['total_issues'] }}</span>
                    @else
                        <span class="badge bg-green">0</span>
                    @endif
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <span class="text-{{ $issues['unclosed_checkins']->count() > 0 ? 'red' : 'muted' }}">⚠</span>
                                Pontaje neînchise (checkout uitat)
                            </span>
                            <a href="{{ backpack_url('presence-event') }}" class="badge bg-{{ $issues['unclosed_checkins']->count() > 0 ? 'red' : 'secondary' }} text-decoration-none">
                                {{ $issues['unclosed_checkins']->count() }}
                            </a>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <span class="text-{{ $issues['leave_while_worked']->count() > 0 ? 'red' : 'muted' }}">⚠</span>
                                Angajați în concediu cu prezență
                            </span>
                            <a href="{{ backpack_url('presence-event') }}" class="badge bg-{{ $issues['leave_while_worked']->count() > 0 ? 'red' : 'secondary' }} text-decoration-none">
                                {{ $issues['leave_while_worked']->count() }}
                            </a>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <span class="text-{{ $issues['long_sessions']->count() > 0 ? 'yellow' : 'muted' }}">⚠</span>
                                Ture &gt; 14 ore luna aceasta
                            </span>
                            <a href="{{ backpack_url('presence-event') }}" class="badge bg-{{ $issues['long_sessions']->count() > 0 ? 'yellow' : 'secondary' }} text-decoration-none">
                                {{ $issues['long_sessions']->count() }}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- D. Issues Detail Tables --}}
    @if($issues['total_issues'] > 0)

        @if($issues['unclosed_checkins']->count() > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">⚠ Pontaje Neînchise ({{ $issues['unclosed_checkins']->count() }})</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Angajat</th>
                                    <th>Data</th>
                                    <th>Locație</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($issues['unclosed_checkins'] as $event)
                                <tr>
                                    <td>{{ $event->employee?->name ?? 'N/A' }}</td>
                                    <td>{{ $event->start_at->format('d.m.Y H:i') }}</td>
                                    <td>{{ $event->workplace?->name ?? '—' }}</td>
                                    <td>
                                        <a href="{{ backpack_url('presence-event/'.$event->id.'/show') }}" class="btn btn-sm btn-light">Edit</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($issues['leave_while_worked']->count() > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">⚠ Conflict Concediu + Prezență ({{ $issues['leave_while_worked']->count() }})</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Angajat</th>
                                    <th>Tip Concediu</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($issues['leave_while_worked'] as $employee)
                                <tr>
                                    <td>{{ $employee->name }}</td>
                                    <td>{{ $employee->leaveRequests->first()?->leaveType?->name ?? 'Concediu' }}</td>
                                    <td>
                                        <a href="{{ backpack_url('presence-event?employee_id='.$employee->id) }}" class="btn btn-sm btn-light">Edit</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($issues['long_sessions']->count() > 0)
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">⚠ Ture Lungi (&gt;14h) luna aceasta ({{ $issues['long_sessions']->count() }})</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Angajat</th>
                                    <th>Data</th>
                                    <th>Durată</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($issues['long_sessions'] as $event)
                                @php
                                    $durationH = round($event->start_at->diffInMinutes($event->end_at) / 60, 1);
                                @endphp
                                <tr>
                                    <td>{{ $event->employee?->name ?? 'N/A' }}</td>
                                    <td>{{ $event->start_at->format('d.m.Y') }}</td>
                                    <td>{{ $durationH }}h</td>
                                    <td>
                                        <a href="{{ backpack_url('presence-event/'.$event->id.'/show') }}" class="btn btn-sm btn-light">Edit</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

    @endif

</div>
@endsection

@section('after_scripts')
<script>
    const aiInsightsUrl = '{{ route('backpack.dashboard.ai-insights') }}';

    function loadAiInsights(refresh) {
        const loadingEl = document.getElementById('ai-loading-state');
        const contentEl = document.getElementById('ai-insights-content');
        const errorEl = document.getElementById('ai-error-state');
        const footerEl = document.getElementById('ai-footer');
        const cachedAtEl = document.getElementById('ai-cached-at');
        const refreshBtn = document.getElementById('ai-refresh-btn');

        loadingEl.classList.remove('d-none');
        contentEl.classList.add('d-none');
        errorEl.classList.add('d-none');
        footerEl.classList.add('d-none');
        refreshBtn.disabled = true;

        const url = refresh ? aiInsightsUrl + '?refresh=1' : aiInsightsUrl;

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(response) {
                if (!response.ok) { throw new Error('HTTP ' + response.status); }
                return response.json();
            })
            .then(function(data) {
                contentEl.innerHTML = data.html;
                cachedAtEl.textContent = data.cached_at;
                loadingEl.classList.add('d-none');
                contentEl.classList.remove('d-none');
                footerEl.classList.remove('d-none');
            })
            .catch(function() {
                loadingEl.classList.add('d-none');
                errorEl.classList.remove('d-none');
            })
            .finally(function() {
                refreshBtn.disabled = false;
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadAiInsights(false);
    });
</script>
@endsection
