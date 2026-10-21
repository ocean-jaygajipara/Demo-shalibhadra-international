<?php

namespace App\Exports;

use App\Helpers\Helper;
use App\Models\MasterUnit;
use App\Models\PurposeVisitMaster;
use App\Models\VisitorGatepass;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Auth;

class VisitorGatepassExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
        $query = VisitorGatepass::with('company', 'purposeOfVisit', 'visitorType', 'teamPerson', 'department',);

        // Apply search filter
        if (isset($this->filter_params->search) && $this->filter_params->search !== '') {
            $query->where(function ($q) {
                $q->where('visitor_name', 'like', '%' . $this->filter_params->search . '%')
                    ->orWhere('contact_number', 'like', '%' . $this->filter_params->search . '%')
                    ->orWhere('email', 'like', '%' . $this->filter_params->search . '%');
            });
        }


        if (!empty($this->filter_params->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        } else if ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user?->company_id);
        }
        // Filter by company
        if ($this->filter_params?->company) {
            $query->where('company_id', $this->filter_params->company);
        }
        if ($this->filter_params?->visitor_type_id) {
            $query->where('visitor_type_id', $this->filter_params->visitor_type_id);
        }
        if ($this->filter_params?->purpose_visit_id) {
            $query->where('purpose_visit_id', $this->filter_params->purpose_visit_id);
        }
        if ($this->filter_params?->person_to_meet) {
            $query->where('team_person_id', $this->filter_params->person_to_meet);
        }
        if ($this->filter_params?->department_id) {
            $query->where('department_id', $this->filter_params->department_id);
        }


        // Filter by status
        if (
            isset($this->filter_params?->status) &&
            $this->filter_params->status !== '' &&
            $this->filter_params->status !== 'all'
        ) {
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
        //  $query = $query->latest();
        // // dd("L-69", $this->filter_params, Helper::interpolateQuery($query->toSql(), $query->getBindings()), $this->user?->toArray());
        // $query = $query->get();
        // return $query;
    }

public function headings(): array
{
    $headings = [
        'Sr',
        'Visitor Name',
        'Contact Number',
        'Email',
        'Visitor Type',
        'Purpose of Visit',
        'Meeting With',
        'Department',
        'Status',
    ];

    if (!$this->user || empty($this->user['company_id'])) {
        array_splice($headings, 1, 0, 'Company Name'); // Add Company Name after 'Sr'
    }

    return $headings;
}


    public function map($row): array
    {


        $rowData = [
            $this->srNo++,
             $row->visitor_name ?? '-',
            $row->contact_number ?? '-',
            $row->email ?? '-',
             $row->visitorType?->name ?? '-',
            $row->purposeOfVisit?->name ?? '-',
               $row->teamPerson?->name ?? '-',
            $row->department?->name ?? '-',

            $row->status ?? '-',
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
        return 'Visitor Gatepass ';
    }
}
