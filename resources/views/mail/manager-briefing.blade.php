<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raport zilnic echipă</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: #1e40af; color: #fff; padding: 24px 32px; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 600; }
        .header p { margin: 4px 0 0; font-size: 14px; opacity: 0.85; }
        .body { padding: 24px 32px; }
        .ai-summary { background: #eff6ff; border-left: 4px solid #3b82f6; padding: 16px; border-radius: 4px; margin-bottom: 24px; }
        .ai-summary p { margin: 0; line-height: 1.6; }
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; }
        .stat-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px 16px; }
        .stat-box .label { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
        .stat-box .value { font-size: 18px; font-weight: 700; color: #111827; }
        .stat-box.alert .value { color: #dc2626; }
        .section { margin-bottom: 20px; }
        .section h3 { font-size: 14px; font-weight: 600; color: #374151; margin: 0 0 8px; text-transform: uppercase; letter-spacing: 0.05em; }
        .tag-list { display: flex; flex-wrap: wrap; gap: 6px; }
        .tag { display: inline-block; background: #f3f4f6; border-radius: 4px; padding: 4px 10px; font-size: 13px; color: #374151; }
        .tag.absent { background: #fef2f2; color: #991b1b; }
        .tag.leave { background: #fffbeb; color: #92400e; }
        .tag.delegation { background: #f0fdf4; color: #166534; }
        .tag.error { background: #fef3c7; color: #92400e; }
        .empty { color: #9ca3af; font-size: 13px; font-style: italic; }
        .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 16px 32px; font-size: 12px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Raport zilnic echipă</h1>
        <p>{{ $teamData['date'] }} &mdash; {{ $teamData['manager']->name }}</p>
    </div>

    <div class="body">
        <div class="ai-summary">
            <p>{!! nl2br(e($briefingText)) !!}</p>
        </div>

        <div class="stats-grid">
            <div class="stat-box">
                <div class="label">Prezenți</div>
                <div class="value">{{ count($teamData['present']) }}</div>
            </div>
            <div class="stat-box {{ count($teamData['absent']) > 0 ? 'alert' : '' }}">
                <div class="label">Absenți</div>
                <div class="value">{{ count($teamData['absent']) }}</div>
            </div>
            <div class="stat-box">
                <div class="label">În concediu</div>
                <div class="value">{{ count($teamData['on_leave']) }}</div>
            </div>
            <div class="stat-box">
                <div class="label">În delegație</div>
                <div class="value">{{ count($teamData['on_delegation']) }}</div>
            </div>
        </div>

        @if(count($teamData['present']) > 0)
        <div class="section">
            <h3>Prezenți la birou</h3>
            <div class="tag-list">
                @foreach($teamData['present'] as $name)
                    <span class="tag">{{ $name }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if(count($teamData['absent']) > 0)
        <div class="section">
            <h3>Absenți (fără pontaj)</h3>
            <div class="tag-list">
                @foreach($teamData['absent'] as $name)
                    <span class="tag absent">{{ $name }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if(count($teamData['on_leave']) > 0)
        <div class="section">
            <h3>În concediu</h3>
            <div class="tag-list">
                @foreach($teamData['on_leave'] as $name)
                    <span class="tag leave">{{ $name }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if(count($teamData['on_delegation']) > 0)
        <div class="section">
            <h3>În delegație</h3>
            <div class="tag-list">
                @foreach($teamData['on_delegation'] as $name)
                    <span class="tag delegation">{{ $name }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if(count($teamData['unclosed_yesterday']) > 0)
        <div class="section">
            <h3>⚠️ Pontaje neînchise (ziua anterioară)</h3>
            <div class="tag-list">
                @foreach($teamData['unclosed_yesterday'] as $name)
                    <span class="tag error">{{ $name }}</span>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="footer">
        Generat automat de Condica &mdash; Sistemul de management al prezenței
    </div>
</div>
</body>
</html>
