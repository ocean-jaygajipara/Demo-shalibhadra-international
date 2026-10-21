<?php

namespace App\Exports;

use App\Models\DocumentType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DocumentTypeExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $srNo = 1;
    protected $filter_params;
    protected $user;
    protected $modules;

    public function __construct($filter_params = [], $user = null, $modules = [])
    {
        $this->filter_params = (object) $filter_params;
        $this->user = $user;
        $this->modules = $modules;
    }

    public function collection()
    {
        $query = DocumentType::with('company');

        if (!$this->filter_params?->company && $this->user?->company_id) {
            $query->where('company_id', $this->user->company_id);
        }

        if (!empty($this->filter_params->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        } else if ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user?->company_id);
        }
        if (isset($this->filter_params->search) && $this->filter_params->search !== '') {
            $search = $this->filter_params->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $this->filter_params->search . '%')
                    ->orWhereHas('company', function ($q2) {
                        $q2->where('company_name', 'like', '%' . $this->filter_params->search . '%');
                    });
            });
        }

        if ($this->filter_params?->status !== null && $this->filter_params->status !== '' && $this->filter_params->status !== 'all') {
            $query->where('status', $this->filter_params->status);
        }

        if ($this->filter_params?->company) {
            $query->where('company_id', $this->filter_params->company);
        }
        if (!empty($this->filter_params->filter_created_by)) {
            $query->where('created_by', $this->filter_params->filter_created_by);
        } elseif ($this->user && $this->user->company_id && empty($this->modules['all_data_permission'])) {
            if (!empty($this->modules['personal_data_permission'])) {
                $query->where('created_by', $this->user->id);
            }
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        $headings = [
            'Sr No',
            'Name',
            'Status',
        ];


        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name'); // Insert after Sr No
        }

        return $headings;
    }


    public function map($row): array
    {
        $data = [
            $this->srNo++,
            $row->name,
            $row->status,
        ];

        // Add company name only if user is not restricted
        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($data, 1, 0, optional($row->company)->company_name ?? '-');
        }

        return $data;
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
