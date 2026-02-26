<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 2px; text-align: center; font-size: 8px; }
        .header { font-weight: bold; background-color: #f2f2f2; }
        .weekend { background-color: #e9ecef; }
        .name-col { text-align: left; width: 150px; }
    </style>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th colspan="{{ 3 + ($daysInMonth * 5) + 6 }}" style="font-size: 14px; font-weight: bold;">
                    CONDICA DE PREZENȚĂ - {{ $monthLabel }}
                </th>
            </tr>
            <tr>
                <th colspan="{{ 3 + ($daysInMonth * 5) + 6 }}" style="text-align: left;">
                    Unitatea: {{ $companyName }}
                </th>
            </tr>
            <tr>
                <th rowspan="2">Nr. Crt.</th>
                <th rowspan="2">Nume și Prenume</th>
                <th rowspan="2">Funcția</th>
                @for($i = 1; $i <= $daysInMonth; $i++)
                    <th colspan="5">Ziua {{ $i }}</th>
                @endfor
                <th colspan="6">Totaluri</th>
            </tr>
            <tr>
                @for($i = 1; $i <= $daysInMonth; $i++)
                    <th>P</th>
                    <th>Ore/Cod</th>
                    <th>1</th>
                    <th>Start</th>
                    <th>End</th>
                @endfor
                <th>Lucrate</th>
                <th>CO</th>
                <th>CM</th>
                <th>I</th>
                <th>75%</th>
                <th>100%</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $row)
                <tr>
                    <td>{{ $row['nr'] }}</td>
                    <td class="name-col">{{ $row['name'] }}</td>
                    <td>{{ $row['role'] }}</td>
                    @foreach($row['days'] as $day)
                        <td class="{{ $day['is_weekend'] ? 'weekend' : '' }}">{{ $day['A'] }}</td>
                        <td class="{{ $day['is_weekend'] ? 'weekend' : '' }}">{{ $day['B'] }}</td>
                        <td class="{{ $day['is_weekend'] ? 'weekend' : '' }}">{{ $day['C'] }}</td>
                        <td class="{{ $day['is_weekend'] ? 'weekend' : '' }}">{{ $day['D'] }}</td>
                        <td class="{{ $day['is_weekend'] ? 'weekend' : '' }}">{{ $day['E'] }}</td>
                    @endforeach
                    <td>{{ $row['totals']['lucrate'] }}</td>
                    <td>{{ $row['totals']['concediu'] }}</td>
                    <td>{{ $row['totals']['concediu_medical'] }}</td>
                    <td>{{ $row['totals']['invoire'] }}</td>
                    <td>{{ $row['totals']['suplimentar75'] }}</td>
                    <td>{{ $row['totals']['suplimentar100'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
