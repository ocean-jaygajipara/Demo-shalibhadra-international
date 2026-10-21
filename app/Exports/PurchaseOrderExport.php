<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseOrderExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
        $query = PurchaseOrder::with(['vendor', 'company']);

       if (!empty($this->filter_params->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        } else if ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user?->company_id);
        }
        if ($this->filter_params?->parent_status_id) {
            $query->where('sales_status_id', $this->filter_params->parent_status_id);
        }

        if ($this->filter_params?->vendor_id) {
            $query->where('vendor_id', $this->filter_params->vendor_id);
        }

        if ($this->filter_params?->purchase_order_date) {

            $filter_purchase_order_date = $this->filter_params?->purchase_order_date;
            $filter_purchase_order_date = explode(" to ", $filter_purchase_order_date);
            $fromDate = null;
            $toDate = null;

            if (count($filter_purchase_order_date) == 2) {
                $fromDate = Helper::convert_date($filter_purchase_order_date[0], "d/m/Y", "Y-m-d");
                $toDate = Helper::convert_date($filter_purchase_order_date[1], "d/m/Y", "Y-m-d");
            }
            if ($fromDate && $toDate) {
                $query->whereDate((new PurchaseOrder())->getTable() . '.purchase_order_date', ">=", $fromDate);
                $query->whereDate((new PurchaseOrder())->getTable() . '.purchase_order_date', "<=", $toDate);
            } elseif ($fromDate) {
                $query->whereDate((new PurchaseOrder())->getTable() . '.purchase_order_date', $fromDate);
            }
        }

        if (isset($this->filter_params->search) && $this->filter_params->search !== '') {
            $search = $this->filter_params->search;

            $query->where(function ($q) use ($search) {
                $q->where('purchase_order_no', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($q2) use ($search) {
                        $q2->where('vendor_name', 'like', "%{$search}%");
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
        //  $query = $query->latest();
        // dd("L-69", $this->filter_params, Helper::interpolateQuery($query->toSql(), $query->getBindings()), $this->user?->toArray());
        // $query = $query->get();
        // return $query;
    }

    public function headings(): array
    {
        $headings = [
            'Sr No',
            'PO NO',
            'PO Date',
            'PO Status',
            'Vendor Name',
            'Team Person Name',
            'Total Amount',
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name');
        }
        return $headings;
    }

    public function map($row): array
    {
        $teamPersonName = $row->created_type === 'admin'
            ? optional($row->admin_person)->name
            : optional($row->team_person)->name;

        $vendorCode = optional($row->vendor)->vendor_code;
        $vendorName = $row->vendor_name ?? optional($row->vendor)->vendor_name;

        if (
            $this->modules['currentGuard'] === 'admin_software' ||
            ($this->modules['currentGuard'] === 'team_person' && empty($this->modules['parent_type_id']))
        ) {
            $vendorInfo = $vendorCode . ' - ' . $vendorName;
        } else {
            $vendorInfo = $vendorCode ?? '-';
        }

        $rows = [
            $this->srNo++,
            $row->purchase_order_no,
            $row->purchase_order_date ? \Carbon\Carbon::parse($row->purchase_order_date)->format('d-m-Y') : '',
            optional($row->sales_status)->statusname,
            $vendorInfo,
            $teamPersonName,
            number_format($row->grand_total, 2),
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
