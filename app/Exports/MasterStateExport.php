<?php

namespace App\Exports;

use App\Models\MasterState;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MasterStateExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $srNo = 1;
    protected $filter_params;
    protected $user;

    public function __construct($filter_params = null, $user = null)
    {
        $this->filter_params = (object) $filter_params;
        $this->user = $user;
        // dd($filter_params);
    }

    public function collection()
    {
        $query = MasterState::with([
            'country'
        ]);

        if (!empty($this->filter_params->search)) {
            $search = $this->filter_params->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhereHas('country', function ($subQuery) use ($search) {
                        $subQuery->where('name', 'like', "%$search%");
                    });
            });
        }


        if (!empty($this->filter_params->country_id)) {
            $query->where('country_id', $this->filter_params->country_id);
        }
        if (isset($this->filter_params->status) && $this->filter_params->status !== '' && $this->filter_params->status !== 'all') {
            $query->where('status', $this->filter_params->status);
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        $headings = ['Sr No', 'Country Name', 'State Name', 'Status'];
        // if (!$this->user || empty($this->user['company_id'])) {
        //     array_splice($headings, 1, 0, 'Country Name');
        // }

        return $headings;
    }

    public function map($row): array
    {
        $mapped = [
            $this->srNo++,
            optional($row->country)->name ?? '-',
            $row->name,
            ucfirst($row->status)
        ];

        // if (!$this->user || empty($this->user['company_id'])) {
        //     array_splice($mapped, 1, 0, optional($row->country)->name ?? 'N/A');
        // }

        return $mapped;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}
