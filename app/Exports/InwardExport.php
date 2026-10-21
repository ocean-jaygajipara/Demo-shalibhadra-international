<?php

namespace App\Exports;

use App\Models\Inward;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InwardExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
        $query = Inward::with(['vendor', 'purchase_order', 'inward_items', 'warehouse','company']);

        if ($this->filter_params?->company) {
            $query->where('company_id', $this->filter_params->company);
        }

        if ($this->filter_params?->inward_date) {

            $filter_inward_date = $this->filter_params?->inward_date;
            $filter_inward_date = explode(" to ", $filter_inward_date);
            $fromDate = null;
            $toDate = null;

            if (count($filter_inward_date) == 2) {
                $fromDate = Helper::convert_date($filter_inward_date[0], "d/m/Y", "Y-m-d");
                $toDate = Helper::convert_date($filter_inward_date[1], "d/m/Y", "Y-m-d");
            }
            if ($fromDate && $toDate) {
                $query->whereDate((new Inward())->getTable() . '.inward_date', ">=", $fromDate);
                $query->whereDate((new Inward())->getTable() . '.inward_date', "<=", $toDate);
            } elseif ($fromDate) {
                $query->whereDate((new Inward())->getTable() . '.inward_date', $fromDate);
            }
        }

        if (isset($this->filter_params->search) && $this->filter_params->search !== '') {
            $search = $this->filter_params->search;

            $query->where(function ($q) use ($search) {
                $q->where('inward_no', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($q2) use ($search) {
                        $q2->where('vendor_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('purchase_order', function ($q2) use ($search) {
                        $q2->where('purchase_order_no', 'like', "%{$search}%");
                    })
                    ->orWhereHas('warehouse', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (Auth::guard('team_person')->check()) {
            $query->where('company_id', $this->user->company_id);
        }
        return $query->latest()->get();
    }

    public function headings(): array
    {
        $headings = [
            'Sr No',
            'Inward No',
            'PO No',
            'Inward Date',
            'Vendor Name',
            'Inward Qty',
            'Warehouse Name',
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name');
        }
        return $headings;
    }

    public function map($row): array
    {

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
            $row->inward_no,
            $row?->purchase_order?->purchase_order_no,
            $row->inward_date ? \Carbon\Carbon::parse($row->inward_date)->format('d-m-Y') : '',
            $vendorInfo,
            $row->inward_items->sum('inward_quantity'),
            $row->warehouse->name,
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
