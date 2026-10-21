<?php

namespace App\Exports;

use App\Models\Dispatch;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DispatchExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $srNo = 1;
    protected $filter_params;
    protected $user;

    protected $modules;


    public function __construct($filter_params = null, $user = null, $modules = [])
    {
        $this->filter_params = (object) $filter_params;
        $this->user = $user;
        $this->modules = $modules;
    }

    public function collection()
    {
        $query = Dispatch::with(['company', 'customer', 'sales_order', 'dispatch_items', 'team_person', 'admin_person']);

        if (!empty($this->filter_params->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        } else if ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user?->company_id);
        }

        if ($this->filter_params?->dispatch_date) {

            $filter_dispatch_date = $this->filter_params?->dispatch_date;
            $filter_dispatch_date = explode(" to ", $filter_dispatch_date);
            $fromDate = null;
            $toDate = null;

            if (count($filter_dispatch_date) == 2) {
                $fromDate = Helper::convert_date($filter_dispatch_date[0], "d/m/Y", "Y-m-d");
                $toDate = Helper::convert_date($filter_dispatch_date[1], "d/m/Y", "Y-m-d");
            }
            if ($fromDate && $toDate) {
                $query->whereDate((new Dispatch())->getTable() . '.dispatch_date', ">=", $fromDate);
                $query->whereDate((new Dispatch())->getTable() . '.dispatch_date', "<=", $toDate);
            } elseif ($fromDate) {
                $query->whereDate((new Dispatch())->getTable() . '.dispatch_date', $fromDate);
            }
        }

        if (isset($this->filter_params->search) && $this->filter_params->search !== '') {
            $search = $this->filter_params->search;

            $query->where(function ($q) use ($search) {
                $q->where('dispatch_no', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q2) use ($search) {
                        $q2->where('customer_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('sales_order', function ($q2) use ($search) {
                        $q2->where('sales_order_no', 'like', "%{$search}%");
                    });
            });
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
            'Dispatch No',
            'Order No',
            'Dispatch Date',
            'Customer Name',
            'Team Person',
            'Dispatch Qty',
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name');
        }
        return $headings;
    }

    public function map($row): array
    {
        $teamPerson = '-';
        if ($row->created_type === 'admin') {
            $teamPerson = $row->admin_person->name ?? '-';
        } else {
            $teamPerson = $row->team_person->name ?? '-';
        }

        $clientCode   = optional($row->customer)->client_code;
        $customerName = optional($row->customer)->customer_name;

        if (
            $this->modules['currentGuard'] === 'admin_software' ||
            ($this->modules['currentGuard'] === 'team_person' && empty($this->modules['parent_type_id']))
        ) {
            $customerInfo = $clientCode . ' - ' . $customerName;
        } else {
            $customerInfo = $clientCode ?? '-';
        }

        $rows = [
            $this->srNo++,
            $row->dispatch_no,
            optional($row->sales_order)->sales_order_no,
            $row->dispatch_date ? \Carbon\Carbon::parse($row->dispatch_date)->format('d-m-Y') : '',
            $customerInfo,
            $teamPerson,
            $row->dispatch_items->sum('dispatch_quantity'),
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($rows, 1, 0, optional($row->company)->company_name ?? '-');
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
        ];
    }
}
