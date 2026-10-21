<?php

namespace App\Exports;

use App\Models\MasterCountry;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MasterCountryExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filter_params;
    protected $counter = 0;  // Add counter property

    public function __construct($filter_params = null)
    {
        $this->filter_params = (object)$filter_params;
    }

    public function collection()
    {
        $query = MasterCountry::query();

        if ($this->filter_params?->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->filter_params?->search . '%')
                    ->orWhere('short_name', 'like', '%' . $this->filter_params?->search . '%')
                    ->orWhere('code', 'like', '%' . $this->filter_params?->search . '%');
            });
        }

        if (
            isset($this->filter_params?->status)
            && $this->filter_params->status !== ''
            && $this->filter_params->status !== 'all'
        ) {
            $query->where('status', $this->filter_params->status);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Sr No',          // Add Serial Number heading
            'Name',
            'Short Name',
            'Status',
        ];
    }

    // Map the data to columns
    public function map($row): array
    {
        $this->counter++; // Increment counter on each row mapped

        return [
            $this->counter,                // Serial number column
            $row->name,
            $row->short_name,
            ucfirst($row->status),
        ];
    }
     public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
        ];
    }

    public function title(): string
    {
        return 'Master Country';
    }
}
