<?php

namespace App\Exports;

use App\Models\Expo;
use App\Models\ExpoManagement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpoManagementExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
        $query = ExpoManagement::with(['company'])
            ->whereNull('deleted_at');

        if (!empty($this->modules['personal_data_permission']) && empty($this->modules['all_data_permission'])) {
            $query->where('created_by', $this->loginUserId);
        }

        // if (Auth::guard('team_person')->check()) {
        //     $query->where('company_id', Auth::guard('team_person')->user()->company_id);
        // }
        if (!empty($this->filter_params->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        } else if ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user?->company_id);
        }

        if ($this->filter_params?->search) {
            $query->where(function ($q) {
                $q->where('expo_name', 'like', '%' . $this->filter_params->search . '%')
                    ->orWhereHas('company', function ($q2) {
                        $q2->where('company_name', 'like', '%' . $this->filter_params->search . '%');
                    });
            });
        }
        // Filter by date range string (dd/mm/yyyy to dd/mm/yyyy)
        if (!empty($this->filter_params->filter_expo_date)) {
            $dates = explode(' to ', $this->filter_params->filter_expo_date);

            if (count($dates) === 2) {
                $from = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                $to = Carbon::createFromFormat('d/m/Y', trim($dates[1]))->endOfDay();

                $query->where(function ($q) use ($from, $to) {
                    $q->whereBetween('start_date', [$from, $to])
                        ->orWhereBetween('end_date', [$from, $to]);
                });
            } elseif (count($dates) === 1) {
                $singleDate = Carbon::createFromFormat('d/m/Y', trim($dates[0]))->startOfDay();
                $query->where(function ($q) use ($singleDate) {
                    $q->whereDate('start_date', $singleDate)
                        ->orWhereDate('end_date', $singleDate);
                });
            }
        }


        if ($this->filter_params?->company) {
            $query->where('company_id', $this->filter_params->company);
        }

        if ($this->filter_params?->expo_category_id) {
            $query->where('expo_category_id', $this->filter_params->expo_category_id);
        }

        if ($this->filter_params?->status !== null && $this->filter_params->status !== 'all') {
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
    }


    public function headings(): array
    {
        $headings = ['Sr No', 'Expo Category Name', 'Expo Name', 'Start Date', 'End Date',  'Location', 'Expo URL','Status'];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name');
        }

        return $headings;
    }

    public function map($expo): array
    {
        // dd($expo);
        $row = [
            $this->srNo++,
            optional($expo->expo_category)->name ?? '-',
            $expo->name,
            optional($expo->start_date instanceof Carbon ? $expo->start_date : Carbon::parse($expo->start_date))->format('d-m-Y'),
            optional($expo->end_date instanceof Carbon ? $expo->end_date : Carbon::parse($expo->end_date))->format('d-m-Y'),
            $expo->location,
            $expo->expo_url,
            $expo->status == 'active' ? 'Active' : 'InActive',
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($row, 1, 0, optional($expo->company)->company_name ?? '-');
        }

        return $row;
    }


    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
        ];
    }
}
