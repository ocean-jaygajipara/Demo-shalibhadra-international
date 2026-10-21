<?php

namespace App\Exports;

use App\Helpers\Helper;
use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Auth;

class CustomerExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
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

        // dd($filter_params);
    }


    public function collection()
    {
        $query = Customer::with(['company', 'country', 'state', 'city', 'area']);

        // Company filter
        // if (!empty($this->filter_params->filter_company)) {
        //     $query->where('company_id', $this->filter_params->filter_company);
        // } elseif ($this->user && $this->user?->company_id) {
        //     $query->where('company_id', $this->user?->company_id);
        // }

        // Search filter
        if (!empty($this->filter_params->search)) {
            $search = $this->filter_params->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%$search%")
                    ->orWhere('contact_person_name', 'like', "%$search%")
                    ->orWhere('mobile_number', 'like', "%$search%");
            });
        }
        if (!empty($this->filter_params?->company)) {
            $query->where('company_id', $this->filter_params->company);
        }
        // Country/state/city/area filters
        if (!empty($this->filter_params->country)) {
            $query->where('country_id', $this->filter_params->country);
        }
        if (!empty($this->filter_params->state)) {
            $query->where('state_id', $this->filter_params->state);
        }
        if (!empty($this->filter_params->city)) {
            $query->where('city_id', $this->filter_params->city);
        }


        // Created by filter
        // if (!empty($this->filter_params->filter_created_by)) {
        //     $query->where('created_by', $this->filter_params->filter_created_by);
        // } elseif ($this->user && $this->user?->id) {
        //     $query->where('created_by', $this->user->id);
        // }

        // Status filter
        if (!empty($this->filter_params->status) && $this->filter_params->status !== 'all') {
            $query->where('status', $this->filter_params->status);
        }
        //   $query = $query->latest();
        //     dd("L-86", $this->filter_params, Helper::interpolateQuery($query->toSql(), $query->getBindings()), $this->user?->toArray());
        //     $query = $query->get();
        //     return $query;
        // dd($sql);
        return $query->latest()->get();
    }

    public function headings(): array
    {
        $headings =  [
            'Sr No',
            'Customer Name',
            'Customer Type',
            'GST Number',
            'Contact Person',
            'Mobile Number',
            'WhatsApp Number',
            'Email',
            'Client Code',
            'Country',
            'State',
            'City',
            'Area',
            'Status'
        ];
        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name');
        }
        return $headings;
    }
    public function map($row): array
    {

        $rowData = [
            $this->srNo++,
            $row->customer_name,
            //    $row->customer_type_id ?? '-',
            optional($row->customerType)->name ?? '-',
            $row->gst_number ?? '-',
            $row->contact_person_name ?? '-',
            $row->mobile_number ?? '-',
            $row->whatsapp_number ?? '-',
            $row->email ?? '-',
            $row->client_code,
            optional($row->country)->name,
            optional($row->state)->name,
            optional($row->city)->name,
            optional($row->area)->area_name,
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
