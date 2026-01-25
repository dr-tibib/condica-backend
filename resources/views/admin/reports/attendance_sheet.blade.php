<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { margin: 15px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; }
        table { width: 100%; border-collapse: collapse; page-break-inside: auto; table-layout: fixed; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        th, td { border: 1px solid black; padding: 2px; text-align: center; vertical-align: middle; }
        .text-left { text-align: left; padding-left: 5px; }
        .truncate { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .truncate div { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .header { margin-bottom: 20px; }
        .weekend { background-color: #f0f0f0; }
        .title { font-size: 14px; font-weight: bold; text-align: center; margin-bottom: 10px; }
        .subtitle { font-size: 10px; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">FOAIA COLECTIVĂ DE PREZENȚĂ (PONTAJ)</div>
        <div class="subtitle">Luna: {{ $monthLabel }}</div>
        <div class="subtitle">Unitatea: {{ $companyName ?? 'My Company' }}</div>
    </div>
    <table>
        <thead>
            <tr>
                <th rowspan="2" width="20">Nr. Crt.</th>
                <th rowspan="2" width="100">Nume și Prenume</th>
                <th rowspan="2" width="60">Funcția</th>
                <th colspan="{{ $daysInMonth }}">Ziua</th>
                <th colspan="5">Total Ore / Zile</th>
            </tr>
            <tr>
                @for($i=1; $i<=$daysInMonth; $i++)
                <th width="15">{{ $i }}</th>
                @endfor
                <th width="30">Total Ore</th>
                <th width="20">CO</th>
                <th width="20">CM</th>
                <th width="20">CFS</th>
                <th width="20">Abs</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $index => $user)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="text-left truncate"><div>{{ $user['name'] }}</div></td>
                <td class="text-left truncate"><div>{{ $user['role'] }}</div></td>
                @foreach($user['days'] as $day)
                    <td class="{{ $day['is_weekend'] ? 'weekend' : '' }}">
                        {{ $day['val'] }}
                    </td>
                @endforeach
                <td>{{ $user['totals']['worked'] }}</td>
                <td>{{ $user['totals']['co'] }}</td>
                <td>{{ $user['totals']['cm'] }}</td>
                <td>{{ $user['totals']['cfs'] }}</td>
                <td>{{ $user['totals']['abs'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px;">
        <table style="border: none; width: 100%;">
            <tr style="border: none;">
                <td style="border: none; text-align: left; width: 33%;">Întocmit,</td>
                <td style="border: none; text-align: center; width: 33%;">Șef Compartiment,</td>
                <td style="border: none; text-align: right; width: 33%;">Aprobat,</td>
            </tr>
        </table>
    </div>
</body>
</html>
