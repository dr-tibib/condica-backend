<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceSheetExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $data;
    protected $view;

    public function __construct($data, $view = 'admin.reports.attendance_sheet')
    {
        $this->data = $data;
        $this->view = $view;
    }

    public function view(): View
    {
        return view($this->view, $this->data);
    }

    public function styles(Worksheet $sheet)
    {
        // Add borders to all cells
        $sheet->getStyle($sheet->calculateWorksheetDimension())
              ->getBorders()
              ->getAllBorders()
              ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        return [];
    }
}
