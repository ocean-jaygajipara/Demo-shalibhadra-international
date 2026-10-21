<?php

namespace App\Exports;

use App\Helpers\Helper;
use App\Models\QcInspection;
use App\Models\JobWorkReceiveItems;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QcInspectionExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
        $query = QcInspection::with(['vendor', 'company','product']);

        if (!empty($this->filter_params?->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        } elseif ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user->company_id);
        }

        if (!empty($this->filter_params?->filter_vendor)) {
            $query->where('vendor_id', $this->filter_params->filter_vendor);
        }

        if (!empty($this->filter_params?->filter_qc_inspection_date)) {
            $dates = explode(' to ', $this->filter_params->filter_qc_inspection_date);

            $fromDate = null;
            $toDate   = null;

            if (count($dates) == 2) {
                $fromDate = Helper::convert_date($dates[0], "d/m/Y", "Y-m-d");
                $toDate   = Helper::convert_date($dates[1], "d/m/Y", "Y-m-d");
            }

            if ($fromDate && $toDate) {
                $query->whereBetween((new QcInspection())->getTable() . '.qc_inspection_date', [$fromDate, $toDate]);
            } elseif ($fromDate) {
                $query->whereDate((new QcInspection())->getTable() . '.qc_inspection_date', $fromDate);
            }
        }


        if (!empty($this->filter_params?->search)) {
            $search = $this->filter_params->search;

            $query->where(function ($q) use ($search) {
                $q->where('qc_inspection_no', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($q2) use ($search) {
                        $q2->where('vendor_name', 'like', "%{$search}%");
                    });
            });
        }


        if (!empty($this->filter_params?->filter_created_by)) {
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
            'Sr',

        ];

        // If not restricted to company, include company name
        if (!$this->user || empty($this->user['company_id'])) {
            $headings[] = 'Company Name';
        }

        $headings = array_merge($headings, [
            'QC Inspection No',
            'QC Inspection Date',
            'Product Name',
            'Vendor Name',
            'Received Weight',
            'Ok Weight',
            'Ok Qty',
            'Team Person Name',
        ]);

        return $headings;
    }

    public function map($row): array
    {
        $rowData = [
            $this->srNo++,

        ];

        // If not restricted to company, insert company name
        if (!$this->user || empty($this->user['company_id'])) {
            $rowData[] = optional($row->company)->company_name ?? '-';
        }

        $team_person_name = '';
        if ($row->created_type == 'admin') {
            $team_person_name = $row?->admin_person?->name;
        } else {
            $team_person_name = $row?->team_person?->name;
        }


        $vendorCode = optional($row->vendor)->vendor_code;
        $vendorName = optional($row->vendor)->vendor_name;

        $vendorInfo = $vendorCode . ' - ' . $vendorName;


        $code = $row->product_code ? "({$row->product_code})" : '';
        $product_name = ($code ?? '-') . ' ' . $row->product_name;

        $rowData = array_merge($rowData, [
            $row->qc_inspection_no ?? '-',
            $row->qc_inspection_date
                ? \Carbon\Carbon::parse($row->qc_inspection_date)->format('d-m-Y')
                : '-',
            $product_name ?? '-',
            $vendorInfo,
            $row->received_weight ?? '-',
            $row->ok_weight ?? '-',
            $row->ok_qty ?? '-',
            $team_person_name ?? '-',
        ]);

        return $rowData;
    }



    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
        ];
    }

}
