<?php

namespace App\Exports;

use App\Models\Complain;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Auth;

class ComplainExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected int $srNo = 1;
    protected object $filter_params;
    protected $user;
    protected $modules;
    public function __construct($filter_params = null, $user = null, $modules = [])
    {
        $this->filter_params = (object) ($filter_params ?? []);
        $this->user = $user;
        $this->modules = $modules;
    }
    public function collection()
    {
        $query = Complain::with([
            'company',
            'sourcecomplain',
            'complaintype',
            'customertype',
            'customer',
            'complaincreatedbyteamperson',
            'complainassigntoteamperson',
        ]);
        // if (Auth::guard('team_person')->check()) {
        //     $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
        //     $query->where('company_id', $teamPersonCompanyId);
        // }
        if (!empty($this->filter_params?->search)) {
            $query->whereHas('customer', function ($q) {
                $q->where('customer_name', 'like', '%' . $this->filter_params->search . '%');
            });
        }
        if (!empty($this->filter_params->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        } else if ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user?->company_id);
        }

        if (!empty($this->filter_params?->company)) {
            $query->where('company_id', $this->filter_params->company);
        }

        if (!empty($this->filter_params?->complain_assign_to_id)) {
            $query->where('complain_assign_to_id', $this->filter_params->complain_assign_to_id);
        }


        if (!empty($this->filter_params?->complain_created_by_id)) {
            $query->where('complain_created_by_id', $this->filter_params->complain_created_by_id);
        }

        if (!empty($this->filter_params?->customer_type_id)) {
            $query->where('customer_type_id', $this->filter_params->customer_type_id);
        }
        // $sql = $query->toSql();
        // $bindings = $query->getBindings();

        // foreach ($bindings as $binding) {
        //     $sql = preg_replace('/\?/', is_numeric($binding) ? $binding : "'{$binding}'", $sql, 1);
        // }

        // dd($sql);

        if (!empty($this->filter_params?->complain_type_id)) {
            $query->where('complain_type_id', $this->filter_params->complain_type_id);
        }

        if (!empty($this->filter_params?->source_complain_id)) {
            $query->where('source_complain_id', $this->filter_params->source_complain_id);
        }

        if (!empty($this->filter_params?->status) && $this->filter_params->status !== 'all') {
            $query->where('status', $this->filter_params->status);
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
            // 'Company Name',
            'Source of Complaint',
            'Complain Type',
            'Customer Type',
            'Customer Name',
            'Remark',
            'Complain Created By',
            'Complain Assign To',
            'Status',
        ];
        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name'); // insert after Sr No
        }

        return $headings;
    }

    public function map($row): array
    {
        $rowData = [
            $this->srNo++,
            optional($row->sourcecomplain)->name ?? '-',
            optional($row->complaintype)->name ?? '-',
            optional($row->customertype)->name ?? '-',
            optional($row->customer)->customer_name ?? '-',
            $row->remark ?? '-',
            optional($row->complaincreatedbyteamperson)->name ?? '-',
            optional($row->complainassigntoteamperson)->name ?? '-',
            $row->status ?? '-',
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($rowData, 1, 0, optional($row->company)->company_name ?? '-');
        }

        return $rowData;
    }


    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }

    public function title(): string
    {
        return 'Complaints';
    }
}
