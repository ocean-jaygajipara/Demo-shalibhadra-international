<?php

namespace App\Exports;

use App\Helpers\Helper;
use App\Models\LeaveApplication;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Auth;

class LeaveApplicationExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $srNo = 1;
    protected $filter_params;
    protected $user;
    protected $modules;
    protected $loginUserId;
    public function __construct($filter_params = null, $user = null, $modules = [], $loginUserId = null)
    {
        $this->filter_params = (object) $filter_params;
        $this->user = $user;
        $this->modules = $modules;
        $this->loginUserId = $loginUserId;
    }
    public function collection()
    {
        $query = LeaveApplication::with('company', 'team_person', 'leave_type');

        if (!empty($this->modules['personal_data_permission']) && empty($this->modules['all_data_permission'])) {
            $query->where('created_by', $this->loginUserId);
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
        if (isset($this->filter_params->search) && $this->filter_params->search !== '') {
            $query->where(function ($q) {
                $q->where('leave_reason', 'like', '%' . $this->filter_params->search . '%')
                    ->orWhereHas('company', function ($q2) {
                        $q2->where('company_name', 'like', '%' . $this->filter_params->search . '%');
                    });
            });
        }
        // Filter by company
        if ($this->filter_params?->company_id) {
            $query->where('company_id', $this->filter_params->company_id);
        }
        if ($this->filter_params?->leave_type) {
            $query->where('leave_type_id', $this->filter_params->leave_type);
        }
        if ($this->filter_params?->halfday_fullday) {
            $query->where('halfday_fullday', $this->filter_params->halfday_fullday);
        }

        if ($this->filter_params?->team_person_ids) {
            $query->where('team_person_ids',   $this->filter_params->team_person_ids);
        }

        if (!empty($this->filter_params->from_date) && !empty($this->filter_params->to_date)) {
            $from = Carbon::createFromFormat('d-m-Y', $this->filter_params->from_date)->startOfDay();
            $to = Carbon::createFromFormat('d-m-Y', $this->filter_params->to_date)->endOfDay();

            $query->where(function ($q) use ($from, $to) {
                $q->whereBetween('fromdate_time', [$from, $to])
                    ->orWhereBetween('todate_time', [$from, $to]);
            });
        }

        // Filter by date range string (dd/mm/yyyy to dd/mm/yyyy)
        if (!empty($this->filter_params->filter_quotation_date)) {
            $dates = explode(' to ', $this->filter_params->filter_quotation_date);

            if (count($dates) === 2) {
                $from = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                $to = Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();

                $query->where(function ($q) use ($from, $to) {
                    $q->whereBetween('fromdate_time', [$from, $to])
                        ->orWhereBetween('todate_time', [$from, $to]);
                });
            } elseif (count($dates) === 1) {
                $singleDate = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                $query->where(function ($q) use ($singleDate) {
                    $q->whereDate('fromdate_time', $singleDate)
                        ->orWhereDate('todate_time', $singleDate);
                });
            }
        }



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
        // dd($this, Helper::interpolateQuery($query?->toSql(), $query?->getBindings()));

        return $query->latest()->get();
    }


    public function headings(): array
    {
        $headings = [
            'Sr No',
            'Team Person Name',
            'Leave Type Name',
            'From Date',
            'To Date',
            'HalfDay / FullDay',
            'Leave Reason',
            'Rejection Reason',
            'Status',
        ];
        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name'); // insert after Sr No
        }

        return $headings;
    }

    public function map($row): array
    {
        $statusText = match (strtolower($row->status)) {
            'approved', 'approve' => 'Approved',
            'rejected', 'reject' => 'Rejected',
            default => 'Pending',
        };

        $rowData = [
            $this->srNo++,
            optional($row->team_person)->name ?? '-',
            optional($row->leave_type)->name ?? '-',
            $row->fromdate_time ? \Carbon\Carbon::parse($row->fromdate_time)->format('d-m-Y H:i') : '-',
            $row->todate_time ? \Carbon\Carbon::parse($row->todate_time)->format('d-m-Y H:i') : '-',
            $row->halfday_fullday ?? '-',
            $row->leave_reason ?? '-',
            $row->rejection_reason ?? '-',
            $statusText,
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            $companyName = $row->relationLoaded('company') && $row->company
                ? $row->company->company_name
                : '-';

            array_splice($rowData, 1, 0, $companyName);
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
