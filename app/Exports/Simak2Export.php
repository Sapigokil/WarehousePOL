<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Simak2Export implements FromView, ShouldAutoSize, WithStyles
{
    protected $year;
    protected $selectedLabel;
    protected $destinations;
    protected $simakDataTab2;

    public function __construct($year, $selectedLabel, $destinations, $simakDataTab2)
    {
        $this->year = $year;
        $this->selectedLabel = $selectedLabel;
        $this->destinations = $destinations;
        $this->simakDataTab2 = $simakDataTab2;
    }

    public function view(): View
    {
        return view('reports.exports.simak2', [
            'year'          => $this->year,
            'selectedLabel' => $this->selectedLabel,
            'destinations'  => $this->destinations,
            'simakDataTab2' => $this->simakDataTab2,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        // Set Default Font Arial ukuran 10 untuk seluruh worksheet Excel agar rapi
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow())
              ->getFont()->setName('Arial')->setSize(10);
    }
}