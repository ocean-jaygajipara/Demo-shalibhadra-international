<?php

namespace App\Exports;

use App\Models\TeamAttendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Auth;


class TeamAttendanceExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
        $query = TeamAttendance::with('company')->whereNull('deleted_at');

        if (isset($this->filter_params->search) && $this->filter_params->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->filter_params->search . '%')
                    ->orWhereHas('company', function ($q2) {
                        $q2->where('company_name', 'like', '%' . $this->filter_params->search . '%');
                    });
            });
        }

        // if (Auth::guard('team_person')->check()) {
        //     $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
        //     $query->where('company_id', $teamPersonCompanyId);
        // }
        if (!empty($this->filter_params->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        } else if ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user?->company_id);
        }
        if ($this->filter_params?->company) {
            $query->where('company_id', $this->filter_params->company);
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
        $headings = [
            'Sr No',
            // 'Company Name',
            'Team Person Name',
            'Punch In Time',
            'Punch In Image Path',
            'Punch In Latitude',
            'Punch In Longitude',
            'Punch In Address',
            'Punch Out Time',
            'Punch Out Image Path',
            'Punch Out Latitude',
            'Punch Out Longitude',
            'Punch Out Address',
            'Punch Out Flag',
            'Working Time',
            'Status',

        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name'); // insert after Sr No
        }

        return $headings;
    }

    public function map($attendance): array
    {
        $rowData = [
            $this->srNo++,
            // optional($attendance->company)->company_name ?? '-',
            optional($attendance->team_person)->name ?? '-',
            $attendance->punch_in_time,
            $attendance->punch_in_image_path,
            $attendance->punch_in_latitude,
            $attendance->punch_in_longitude,
            $attendance->punch_in_address,
            $attendance->punch_out_time,
            $attendance->punch_out_image_path,
            $attendance->punch_out_latitude,
            $attendance->punch_out_longitude,
            $attendance->punch_out_address,
            $attendance->punch_out_flag,
            $attendance->working_time,
            $attendance->status ? 'Active' : 'Inactive',

        ];
        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($rowData, 1, 0, optional($attendance->company)->company_name ?? '-');
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
        return 'Team Attendance';
    }
}
