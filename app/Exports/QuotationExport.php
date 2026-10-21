<?php

namespace App\Exports;

use App\Models\Quotation;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QuotationExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $srNo = 1;
    protected $modules = [];

    protected $filter_params;
    protected $user;
    protected $showGrandTotal;

    public function __construct($filter_params = null, $user = null, $modules = [])
    {
        $this->filter_params = (object) $filter_params;
        $this->user = $user;
        $this->modules = $modules;

        $this->showGrandTotal = (
            $modules['currentGuard'] === 'admin_software' ||
            ($modules['currentGuard'] === 'team_person' && empty($modules['parent_type_id']))
        );
    }

    public function collection()
    {
        $query = Quotation::with(['company', 'customer', 'team_person', 'admin_person']);

        if ($this->filter_params?->company) {
            $query->where('company_id', $this->filter_params->company);
        }

        if ($this->filter_params?->parent_status_id) {
            $query->where('sales_status_id', $this->filter_params->parent_status_id);
        }



        if ($this->filter_params?->team_person) {
            $query->where('created_by', $this->filter_params->team_person)->where('created_type', 'team');
        }

        if ($this->filter_params?->quotation_date) {

            $filter_quotation_date = $this->filter_params?->quotation_date;
            $filter_quotation_date = explode(" to ", $filter_quotation_date);
            $fromDate = null;
            $toDate = null;

            if (count($filter_quotation_date) == 2) {
                $fromDate = Helper::convert_date($filter_quotation_date[0], "d/m/Y", "Y-m-d");
                $toDate = Helper::convert_date($filter_quotation_date[1], "d/m/Y", "Y-m-d");
            }
            if ($fromDate && $toDate) {
                $query->whereDate((new Quotation())->getTable() . '.quotation_date', ">=", $fromDate);
                $query->whereDate((new Quotation())->getTable() . '.quotation_date', "<=", $toDate);
            } elseif ($fromDate) {
                $query->whereDate((new Quotation())->getTable() . '.quotation_date', $fromDate);
            }
        }

        if (isset($this->filter_params->search) && $this->filter_params->search !== '') {
            $search = $this->filter_params->search;

            $query->where(function ($q) use ($search) {
                $q->where('quotation_no', 'like', "%{$search}%")
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



        if (!empty($this->filter_params->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        } else if ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user?->company_id);
        }

        $query = $query->latest();
        // dd("L-86", $this->filter_params, Helper::interpolateQuery($query->toSql(), $query->getBindings()), $this->user?->toArray());
        $query = $query->get();
        return $query;
    }

    public function headings(): array
    {
        $headings = [
            'Sr No',
            'Quotation No',
            'Quotation Date',
            'Quotation Status',
            'Customer Name',
            'Team Person Name',
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name');
        }

        if ($this->showGrandTotal) {
            $headings[] = 'Total Amount';
        }
        return $headings;
    }

    public function map($row): array
    {

        $reviseOrRepeat = '';
        if (!empty($row->quotation_revise)) {
            $reviseOrRepeat .= ' (Revise: ' . $row->parent_quotation_id . ')';
        }
        if (!empty($row->quotation_repeat)) {
            $reviseOrRepeat .= ' (Repeat: ' . $row->parent_quotation_id . ')';
        }
        $team_person_name = '';
        if ($row->created_type == 'admin') {
            $team_person_name = $row->admin_person->name;
        } else {
            $team_person_name = $row->team_person->name;
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
            $row->quotation_no . $reviseOrRepeat,
            $row->quotation_date ? \Carbon\Carbon::parse($row->quotation_date)->format('d-m-Y') : '',
            optional($row->sales_status)->statusname,
            $customerInfo,
            $team_person_name,
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($rows, 1, 0, optional($row->company)->company_name ?? '-');
        }

        if ($this->showGrandTotal) {
            $rows[] = number_format($row->grand_total, 2);
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
