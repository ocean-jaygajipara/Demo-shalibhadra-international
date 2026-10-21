<?php

namespace App\Exports;

use App\Models\SalesOrder;
use App\Models\PackingSlip;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PackingSlipExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $srNo = 1;
    protected $filter_params;
    protected $user;

    protected $modules;

    public function __construct($filter_params = null, $user = null,$modules = [])
    {
        $this->filter_params = (object) $filter_params;
        $this->user = $user;
        $this->modules = $modules;
    }

    public function collection()
    {
        $query =  PackingSlip::with(['company', 'customer','packing_slip_item']);
          if (!empty($this->filter_params->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        } else if ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user?->company_id);
        }

        if ($this->filter_params?->packing_slip_date) {

            $packing_slip_date = $this->filter_params?->packing_slip_date;
            $packing_slip_date = explode(" to ", $packing_slip_date);
            $fromDate = null;
            $toDate = null;

            if (count($packing_slip_date) == 2) {
                $fromDate = Helper::convert_date($packing_slip_date[0], "d/m/Y", "Y-m-d");
                $toDate = Helper::convert_date($packing_slip_date[1], "d/m/Y", "Y-m-d");
            }
            if ($fromDate && $toDate) {
                $query->whereDate((new PackingSlip())->getTable() . '.packing_slip_date', ">=", $fromDate);
                $query->whereDate((new PackingSlip())->getTable() . '.packing_slip_date', "<=", $toDate);
            } elseif ($fromDate) {
                $query->whereDate((new PackingSlip())->getTable() . '.packing_slip_date', $fromDate);
            }
        }

        if (isset($this->filter_params->search) && $this->filter_params->search !== '') {
            $search = $this->filter_params->search;

            $query->where(function ($q) use ($search) {
                $q->where('packing_slip_no', 'like', "%{$search}%")
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
        } elseif ($this->user &&$this->user->company_id && empty($this->modules['all_data_permission'])) {
            if (!empty($this->modules['personal_data_permission'])) {
                $query->where('created_by', $this->user->id);
            }
        }
        $query = $query->latest();
        $query = $query->get();
        return $query;
    }

    public function headings(): array
    {
        $headings = [
            'Sr No',
            'Packing slip No',
            'Sales Order No',
            'Packing Slip Date',
            'Packing Slip Status',
            'Customer Name',
            'Packing Slip Qty',
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name');
        }
        return $headings;
    }

    public function map($row): array
    {

        $packing_slip_qty = 0;
        if ($row->packing_slip_item && $row->packing_slip_item->count() > 0) {
            $totalQtyPc = $row->packing_slip_item->sum('qty_pc');
            $packing_slip_qty = number_format($totalQtyPc, 2);
        }

        $clientCode   = optional($row->customer)->client_code;
        $customerName = $row->customer_name ?? optional($row->customer)->customer_name;

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
            $row->packing_slip_no,
            $row->sales_order_no,
            $row->packing_slip_date ? \Carbon\Carbon::parse($row->packing_slip_date)->format('d-m-Y') : '',
            ucfirst($row?->sales_status?->statusname),
            $customerInfo,
            $packing_slip_qty,
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
