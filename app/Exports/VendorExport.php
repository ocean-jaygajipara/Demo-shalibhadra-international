<?php

namespace App\Exports;

use App\Models\Vendor;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Auth;

class VendorExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
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
        $query = Vendor::with(['company', 'country', 'state', 'city', 'area', 'team_person', 'admin_person']);

        if ($this->filter_params?->search && $this->filter_params->search !== '') {
            $search = $this->filter_params->search;
            $query->where(function ($q) use ($search) {
                $q->where('vendor_name', 'like', "%{$search}%")
                    ->orWhere('vendor_code', 'like', "%{$search}%");
            });
        }

        if (!empty($this->filter_params->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        } else if ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user?->company_id);
        }
        if ($this->filter_params?->status !== null && $this->filter_params->status !== '' && $this->filter_params->status !== 'all') {
            $query->where('status', $this->filter_params->status);
        }
        if ($this->filter_params?->company) {
            $query->where('company_id', $this->filter_params->company);
        }
        if ($this->filter_params?->country) {
            $query->where('country_id', $this->filter_params->country);
        }

        if ($this->filter_params?->state) {
            $query->where('state_id', $this->filter_params->state);
        }
        if ($this->filter_params?->city) {
            $query->where('city_id', $this->filter_params->city);
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
        $headings =  [
            'Sr No',
            'Vendor Name',
            'Gst Number',
            'Mobile Number',
            'Whatsapp Number',
            'Email Id',
            'Vendor Code',
            'Country',
            'State',
            'City',
            'Area',
            // 'Team Person Name',
            'Status',
        ];
        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name');
        }
        return $headings;
    }
    public function map($row): array
    {
        $teamPersonName = $row->created_type === 'admin'
            ? optional($row->admin_person)->name?? '-'
            : optional($row->team_person)->name?? '-';


        $rowData = [
            $this->srNo++,
            ($row->vendor_name ?? '-'),
            ($row->gst_number ?? '-'),
            ($row->mobile_number ?? '-'),
            ($row->whatsapp_number ?? '-'),
            ($row->email_id ?? '-'),
            $row->vendor_code ?? '-',
            optional($row->country)->name ?? '-',
            optional($row->state)->name ?? '-',
            optional($row->city)->name ?? '-',
            optional($row->area)->area_name ?? '-',
            // $teamPersonName ?? '-',
            $row->status,
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($rowData, 1, 0, optional($row->company)->company_name ?? '-');
        }

        return $rowData;
    }
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
        ];
    }

    public function title(): string
    {
        return 'Customer';
    }
}
