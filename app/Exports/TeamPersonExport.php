<?php

namespace App\Exports;

use App\Models\TeamPerson;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Auth;


class TeamPersonExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $srNo = 1;
    protected $filter_params;
    protected $user;
    protected $modules;
    protected $loginUserId;

     public function __construct($filter_params = null, $user = null, $modules = null, $loginUserId = null)
    {
        $this->filter_params = (object) $filter_params;
        $this->user = $user;
        $this->modules = $modules;
        $this->loginUserId = $loginUserId;
    }
    public function collection()
    {
        $query = TeamPerson::with('company','country','state','city','designation','team_role')->whereNull('deleted_at');
         if (!empty($this->modules['personal_data_permission']) && empty($this->modules['all_data_permission'])) {
            $query->where('created_by', $this->loginUserId);
        }

        if (Auth::guard('team_person')->check()) {
            $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
            $query->where('company_id', $teamPersonCompanyId);
        }

          if ($this->filter_params?->search && $this->filter_params->search !== '') {
            $search = $this->filter_params->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhereHas('company', function ($q2) use ($search) {
                        $q2->where('company_name', 'like', "%$search%");
                    });
            });
        }
         if (Auth::guard('team_person')->check()) {
            $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
            $query->where('company_id', $teamPersonCompanyId);
        }
         if ($this->filter_params?->company) {
            $query->where('company_id', $this->filter_params->company);
        }



        if ($this->filter_params?->country_id) {
            $query->where('country_id', $this->filter_params->country_id);
        }

        if ($this->filter_params?->state_id) {
            $query->where('state_id', $this->filter_params->state_id);
        }
        if ($this->filter_params?->city_id) {
            $query->where('city_id', $this->filter_params->city_id);
        }
        if($this->filter_params->designation_id){
            $query->where('designation_id',$this->filter_params->designation_id);
        }

        if($this->filter_params->team_role_id){
            $query->where('team_role_id',$this->filter_params->team_role_id);
        }
        return $query->latest()->get();
    }

    public function headings(): array
    {
        $headings = [
            'Sr No',
            'Name',
            'Mobile Number',
            'Email',
            'User Name',
            'Password',
            'Country',
            'State',
            'City',
            'Designation',
            'Role',
            'Status',
        ];
        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name'); // insert after Sr No
        }

        return $headings;
    }

    public function map($person): array
    {
         $rowData = [
            $this->srNo++,
            $person->name ?? '-',
            $person->mobile_no ?? '-',
            $person->email ?? '-',
            $person->username ?? '-',
            $person->password ?? '-',
            optional($person->country)->name ?? '-',
            optional($person->state)->name ?? '-',
            optional($person->city)->name ?? '-',
            optional($person->designation)->name ?? '-',
            optional($person->role)->name ?? '-',
            $person->status ? 'Active' : 'Inactive',
        ];
         if (!$this->user || empty($this->user['company_id'])) {
            array_splice($rowData, 1, 0, optional($person->company)->company_name ?? '-');
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
        return 'FollowUp Reasons';
    }
}
