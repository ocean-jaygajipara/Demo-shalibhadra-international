<?php

namespace App\Exports;

use App\Models\GRN;
use App\Models\GRNItem;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GRNExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
        $query =  GRN::with(['company', 'customer','grn_item']);
          if (!empty($this->filter_params->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        } else if ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user?->company_id);
        }

        if ($this->filter_params?->grn_date) {

            $grn_date = $this->filter_params?->grn_date;
            $grn_date = explode(" to ", $grn_date);
            $fromDate = null;
            $toDate = null;

            if (count($grn_date) == 2) {
                $fromDate = Helper::convert_date($grn_date[0], "d/m/Y", "Y-m-d");
                $toDate = Helper::convert_date($grn_date[1], "d/m/Y", "Y-m-d");
            }
            if ($fromDate && $toDate) {
                $query->whereDate((new GRN())->getTable() . '.grn_date', ">=", $fromDate);
                $query->whereDate((new GRN())->getTable() . '.grn_date', "<=", $toDate);
            } elseif ($fromDate) {
                $query->whereDate((new GRN())->getTable() . '.grn_date', $fromDate);
            }
        }

        if (isset($this->filter_params->search) && $this->filter_params->search !== '') {
            $search = $this->filter_params->search;

            $query->where(function ($q) use ($search) {
                $q->where('grn_no', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q2) use ($search) {
                        $q2->where('customer_name', 'like', "%{$search}%");
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
            'GRN No',
            'GRN Date',
            'DOS Date',
            'GRN Status',
            'Customer Name',
            'Team Person Name',
            'Total QTY',
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name');
        }
        return $headings;
    }

    public function map($row): array
    {

        $team_person_name = '';
        if ($row->created_type == 'admin') {
            $team_person_name = $row->admin_person->name;
        } else {
            $team_person_name = $row->team_person->name;
        }

        $total_qty = 0;
        if ($row->grn_item && $row->grn_item->count() > 0) {
            $totalQtyPc = $row->grn_item->sum('quantity');
            $total_qty = number_format($totalQtyPc, 2);
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
            $row->grn_no,
            $row->grn_date ? \Carbon\Carbon::parse($row->grn_date)->format('d-m-Y') : '',
            $row->dos_date ? \Carbon\Carbon::parse($row->dos_date)->format('d-m-Y') : '',
            ucfirst($row?->sales_status?->statusname),
            $customerInfo,
            $team_person_name ?? '-',
            $total_qty,
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
