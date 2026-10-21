<?php

namespace App\Exports;


use App\Models\ExpoVisitor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpoVisitorExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
        $query = ExpoVisitor::with(['company'])->whereNull('deleted_at');

        // if (!empty($this->modules['personal_data_permission']) && empty($this->modules['all_data_permission'])) {
        //     $query->where('created_by', $this->loginUserId);
        // }

        // if (Auth::guard('team_person')->check()) {
        //     $query->where('company_id', Auth::guard('team_person')->user()->company_id);
        // }
        if (!empty($this->filter_params->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        } else if ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user?->company_id);
        }

        if ($this->filter_params?->search) {
            $search = $this->filter_params->search;
            $query->where(function ($q) use ($search) {
                $q->where('person_name', 'like', "%$search%")
                    ->orWhere('mobile_no', 'like', "%$search%")
                    ->orWhere('company_name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('requirement', 'like', "%$search%");
            });
        }

        if ($this->filter_params?->filter_company) {
            $query->where('company_id', $this->filter_params->filter_company);
        }

        if ($this->filter_params?->filter_expo) {
            $query->where('expo_id', $this->filter_params->filter_expo);
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
            'Company Name',
            'Person Name',
            'Mobile No',
            'Email',
            'Website',
            'Requirement'
        ];
        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company By');
        }
        return $headings;
    }

    public function map($row): array
    {
        $rows = [
            $this->srNo++,
            $row->company_name ?? '-',
            $row->person_name ?? '-',
            $row->mobile_no ?? '-',
            $row->email ?? '-',
            $row->website ?? '-',
            $row->requirement ?? '-',
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
