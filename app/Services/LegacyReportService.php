<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LegacyReportService
{
    private $templatePath;

    public function __construct()
    {
        $this->templatePath = resource_path('templates/excel/FoaieColectivaPrezenta20243.xls');
    }

    public function generateLegacyData($year, $month)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $daysInMonth = $startDate->daysInMonth;

        $employees = Employee::with([
            'department',
            'presenceEvents' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_at', [$startDate, $endDate])
                    ->orderBy('start_at');
            },
            'leaveRequests' => function ($q) use ($startDate, $endDate) {
                $q->where('status', 'APPROVED')
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('start_date', [$startDate, $endDate])
                            ->orWhereBetween('end_date', [$startDate, $endDate])
                            ->orWhere(function ($sub) use ($startDate, $endDate) {
                                $sub->where('start_date', '<', $startDate)
                                    ->where('end_date', '>', $endDate);
                            });
                    });
            },
        ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $dataArray = [];
        $employeeCount = 0;

        $monthsRo = ['Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie', 'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'];

        foreach ($employees as $employee) {
            $employeeCount++;
            $row = [
                'nr' => $employeeCount,
                'name' => strtoupper($employee->last_name).' '.$employee->first_name,
                'role' => $employee->department->name ?? '-',
                'worked' => 0,
                'co' => 0,
                'cm' => 0,
                'permit' => 0,
                'suplimentar75' => 0,
                'suplimentar100' => 0,
                'blank' => '  ',
            ];

            for ($d = 1; $d <= 31; $d++) {
                $row["A$d"] = '';
                $row["B$d"] = 'X';
                $row["C$d"] = '';
                $row["D$d"] = '';
                $row["E$d"] = '';
            }

            foreach ($employee->leaveRequests as $leave) {
                $leaveStart = Carbon::parse($leave->start_date);
                $leaveEnd = Carbon::parse($leave->end_date);

                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $currentDay = $startDate->copy()->addDays($d - 1);
                    if ($currentDay->between($leaveStart, $leaveEnd)) {
                        $code = 'C';
                        if ($leave->leaveType) {
                            if ($leave->leaveType->medical_code_required) {
                                $code = 'Bo';
                                $row['cm']++;
                            } elseif (! $leave->leaveType->is_paid) {
                                $code = 'I';
                                $row['permit']++;
                            } else {
                                $row['co']++;
                            }
                        } else {
                            $row['co']++;
                        }

                        $row["A$d"] = '';
                        $row["B$d"] = $code;
                        $row["C$d"] = '';
                        $row["D$d"] = '';
                        $row["E$d"] = '';
                    }
                }
            }

            $groupedEvents = $employee->presenceEvents->groupBy(function ($event) {
                return $event->start_at->day;
            });

            foreach ($groupedEvents as $day => $events) {
                $totalMinutes = 0;
                foreach ($events as $event) {
                    if ($event->end_at && $event->start_at->lt($event->end_at)) {
                        $totalMinutes += $event->start_at->diffInMinutes($event->end_at);
                    }
                }

                $timeSpentHours = round($totalMinutes / 60, 1);
                $currentDay = $startDate->copy()->addDays($day - 1);
                $dayOfWeek = $currentDay->dayOfWeekIso - 1; // 0=Mon, 6=Sun

                $results = $this->calculateStartEndAndTime($timeSpentHours, $dayOfWeek);

                if ($timeSpentHours > 0) {
                    $row["A$day"] = 'P';
                    $row["B$day"] = $results['timeSpent'];
                    $row["C$day"] = '1';
                    $row["D$day"] = $results['workedStart'];
                    $row["E$day"] = $results['workedEnd'];

                    $row['worked'] += $results['timeSpent'];
                    $row['suplimentar75'] += $results['suplimentar75'];
                    $row['suplimentar100'] += $results['suplimentar100'];
                }
            }

            $row['total'] = $row['worked'];
            $row['oreSuplomentare75'] = $row['suplimentar75'];
            $row['oreSuplomentare100'] = $row['suplimentar100'];
            $row['holiday'] = $row['co'] ?: '';
            $row['sickleave'] = $row['cm'] ?: '';
            $row['permit'] = $row['permit'] ?: '';

            $dataArray[] = $row;
        }

        return [
            'v' => $dataArray,
            'k' => [
                'month' => $monthsRo[$month - 1],
                'year' => $year,
            ],
            'companyName' => (string) (tenant('company_name') ?? tenant('id') ?? 'Condica'),
        ];
    }

    public function downloadWithTemplate($year, $month)
    {
        $data = $this->generateLegacyData($year, $month);

        $spreadsheet = IOFactory::load($this->templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        $highestCol = $sheet->getHighestColumn();
        $highestColIndex = Coordinate::columnIndexFromString($highestCol);
        $vRow = null;

        // 1. Fill Constants and Unitatea
        $highestRowLimit = 15; // Constants are at the top
        for ($row = 1; $row <= $highestRowLimit; $row++) {
            for ($col = 1; $col <= $highestColIndex; $col++) {
                $cell = $sheet->getCellByColumnAndRow($col, $row);
                $value = $cell->getValue();
                if (is_string($value)) {
                    if (str_contains($value, '{k:')) {
                        foreach ($data['k'] as $key => $val) {
                            $value = str_replace("{k:$key}", (string) $val, $value);
                        }
                        $cell->setValue($value);
                    }
                    if (str_contains($value, 'Unitatea:')) {
                        $value = preg_replace('/Unitatea:.*$/', 'Unitatea: '.$data['companyName'], $value);
                        $cell->setValue($value);
                    }
                    if (! $vRow && str_contains($value, '{v:')) {
                        $vRow = $row;
                    }
                }
            }
        }

        // 2. Handle Repeating Employee Block
        $blockSize = 6;
        $rowData = $data['v'];
        $numEmployees = count($rowData);

        if ($numEmployees > 0 && $vRow) {
            $startRow = $vRow;

            // 2.1 Cache template block
            $templateBlockValues = [];
            $templateBlockHeights = [];
            $templateMerging = [];

            for ($r = 0; $r < $blockSize; $r++) {
                $actualRow = $startRow + $r;
                $templateBlockHeights[$r] = $sheet->getRowDimension($actualRow)->getRowHeight();
                for ($col = 1; $col <= $highestColIndex; $col++) {
                    $templateBlockValues[$r][$col] = $sheet->getCellByColumnAndRow($col, $actualRow)->getValue();
                }
            }

            foreach ($sheet->getMergeCells() as $mergeRange) {
                [$rangeStart, $rangeEnd] = Coordinate::rangeBoundaries($mergeRange);
                if ($rangeStart[1] >= $startRow && $rangeEnd[1] < $startRow + $blockSize) {
                    $templateMerging[] = [
                        'colStart' => $rangeStart[0],
                        'rowStartOffset' => $rangeStart[1] - $startRow,
                        'colEnd' => $rangeEnd[0],
                        'rowEndOffset' => $rangeEnd[1] - $startRow,
                    ];
                }
            }

            // 2.2 Insert all rows at once
            if ($numEmployees > 1) {
                $sheet->insertNewRowBefore($startRow + $blockSize, ($numEmployees - 1) * $blockSize);
            }

            // 2.3 Fill data
            foreach ($rowData as $i => $employeeData) {
                $employeeBaseRow = $startRow + ($i * $blockSize);

                for ($r = 0; $r < $blockSize; $r++) {
                    $currentRow = $employeeBaseRow + $r;
                    $templateRow = $startRow + $r;

                    if ($templateBlockHeights[$r] != -1) {
                        $sheet->getRowDimension($currentRow)->setRowHeight($templateBlockHeights[$r]);
                    }

                    // Style duplication per row range
                    if ($i > 0) {
                        $sheet->duplicateStyle(
                            $sheet->getStyle("A{$templateRow}:{$highestCol}{$templateRow}"),
                            "A{$currentRow}:{$highestCol}{$currentRow}"
                        );
                    }

                    // Process values
                    $rowValues = [];
                    for ($col = 1; $col <= $highestColIndex; $col++) {
                        $templateValue = $templateBlockValues[$r][$col];
                        if (is_string($templateValue) && str_contains($templateValue, '{v:')) {
                            $newValue = preg_replace_callback('/\{v:([^\}]+)\}/', function ($m) use ($employeeData) {
                                return (string) ($employeeData[$m[1]] ?? '');
                            }, $templateValue);
                            $sheet->setCellValueByColumnAndRow($col, $currentRow, $newValue);
                        } else {
                            if ($i > 0 && $templateValue !== null) {
                                $sheet->setCellValueByColumnAndRow($col, $currentRow, $templateValue);
                            }
                        }
                    }
                }

                if ($i > 0) {
                    foreach ($templateMerging as $m) {
                        $sheet->mergeCells(
                            Coordinate::stringFromColumnIndex($m['colStart']).($employeeBaseRow + $m['rowStartOffset']).':'.
                            Coordinate::stringFromColumnIndex($m['colEnd']).($employeeBaseRow + $m['rowEndOffset'])
                        );
                    }
                }
            }
        }

        $filename = 'Condica_Prezenta_'.$year.'_'.str_pad($month, 2, '0', STR_PAD_LEFT).'.xls';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = IOFactory::createWriter($spreadsheet, 'Xls');
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment;filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function calculateStartEndAndTime($timeSpentHours, $dayOfWeek)
    {
        $suplimentar75 = 0;
        $suplimentar100 = 0;
        $workedStart = '08:00';
        $workedEnd = '16:30';
        $timeSpent = 8;

        if ($timeSpentHours == 0) {
            return [
                'timeSpent' => 0,
                'workedStart' => '',
                'workedEnd' => '',
                'suplimentar75' => 0,
                'suplimentar100' => 0,
            ];
        }

        if ($dayOfWeek <= 4) {
            $timeThresholds = [
                9.5 => ['timeSpent' => 9,  'upper' => 10.5,  'workedEnd' => '17:30', 'additionalTime' => 1],
                10.5 => ['timeSpent' => 10, 'upper' => 11.5, 'workedEnd' => '18:30', 'additionalTime' => 2],
                11.5 => ['timeSpent' => 11, 'upper' => 12.5, 'workedEnd' => '19:30', 'additionalTime' => 3],
                12.5 => ['timeSpent' => 12, 'upper' => 24.0, 'workedEnd' => '20:30', 'additionalTime' => 4],
            ];

            foreach ($timeThresholds as $threshold => $values) {
                if ($timeSpentHours >= $threshold && $timeSpentHours < $values['upper']) {
                    $timeSpent = $values['timeSpent'];
                    $workedEnd = $values['workedEnd'];
                    $suplimentar75 = $values['additionalTime'];
                    break;
                }
            }

            if ($timeSpentHours < 9.5) {
                $timeSpent = 8;
                $workedEnd = '16:30';
            }
        } else {
            $suplimentar100 = round($timeSpentHours);
            $workedStart = '08:00';
            $addMinutes = ($suplimentar100 == 8) ? 30 : 0;
            $workedEnd = Carbon::createFromFormat('H:i', $workedStart)
                ->addHours($suplimentar100)
                ->addMinutes($addMinutes)
                ->format('H:i');
            $timeSpent = $suplimentar100;
        }

        return [
            'timeSpent' => $timeSpent,
            'workedStart' => $workedStart,
            'workedEnd' => $workedEnd,
            'suplimentar75' => $suplimentar75,
            'suplimentar100' => $suplimentar100,
        ];
    }
}
