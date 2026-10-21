<?php

namespace App\Exports;

use App\Models\DocumentList;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DocumentListExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $srNo = 1;

    protected $filter_params;
    protected $user;
    protected $modules;


    public function __construct($filter_params = [], $user = null, $modules = [])
    {
        // dd($filter_params);
        $this->filter_params = (object) $filter_params;
        $this->user = $user;
        $this->modules = $modules;
    }

    public function collection()
    {

        $query = DocumentList::with(['document_type', 'company']);

        if (!empty($this->filter_params->search)) {
            $query->where('name', 'like', '%' . $this->filter_params->search . '%');
        }
        if (!empty($this->filter_params->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        } else if ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user?->company_id);
        }
        if (
            isset($this->filter_params->status) &&
            $this->filter_params->status !== '' &&
            $this->filter_params->status !== 'all'
        ) {
            $query->where('status', $this->filter_params->status);
        }

        if (!empty($this->filter_params->company)) {
            $query->where('company_id', $this->filter_params->company);
        }

        if (!empty($this->filter_params->document_type_id)) {
            $query->where('document_type_id', $this->filter_params->document_type_id);
        }


        if (!empty($this->filter_params->filter_created_by)) {
            $query->where('created_by', $this->filter_params->filter_created_by);
        } elseif ($this->user && $this->user->company_id && empty($this->modules['all_data_permission'])) {
            if (!empty($this->modules['personal_data_permission'])) {
                $query->where('created_by', $this->user->id);
            }
        }
        // dd($query->toSql(), $query->getBindings());
        return $query->latest()->get();
    }

    public function headings(): array
    {
        $headings = [
            'Sr No',
            // 'Company Name',
            'Document Type Name',
            'Name',
            'Status',
        ];
        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name'); // insert after Sr No
        }
        return $headings;
    }

    public function map($row): array
    {
        $data = [
            $this->srNo++,
            // optional($row->company)->company_name ?? '-',
            optional($row->document_type)->name ?? '-',
            $row->name,
            ucfirst($row->status),
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($data, 1, 0, optional($row->company)->company_name ?? '-'); // insert after Sr No
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];


        return $styles;
    }
}
