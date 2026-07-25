<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SimakExport implements FromView, ShouldAutoSize, WithStyles
{
    protected $month;
    protected $year;
    protected $simakHeaders;
    protected $destinations;
    protected $simakData;
    protected $monthName;

    public function __construct($month, $year, $simakHeaders, $destinations, $simakData, $monthName)
    {
        $this->month = $month;
        $this->year = $year;
        $this->simakHeaders = $simakHeaders;
        $this->destinations = $destinations;
        $this->simakData = $simakData;
        $this->monthName = $monthName;
    }

    public function view(): View
    {
        return view('reports.exports.simak', [
            'month'        => $this->month,
            'year'         => $this->year,
            'simakHeaders' => $this->simakHeaders,
            'destinations' => $this->destinations,
            'simakData'    => $this->simakData,
            'monthName'    => $this->monthName,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        // Set Default Font Arial ukuran 10 untuk seluruh worksheet Excel agar rapi
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow())
              ->getFont()->setName('Arial')->setSize(10);
    }
}