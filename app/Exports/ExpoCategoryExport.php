<?php

namespace App\Exports;

use App\Models\ExpoCategory;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpoCategoryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
        $query = ExpoCategory::with('company')->whereNull('deleted_at');
        if (!empty($this->modules['personal_data_permission']) && empty($this->modules['all_data_permission'])) {
            $query->where('created_by', $this->loginUserId);
        }

        if (Auth::guard('team_person')->check()) {
            $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
            $query->where('company_id', $teamPersonCompanyId);
        }
        // Apply search filter if present
        if (isset($this->filter_params->search) && $this->filter_params->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->filter_params->search . '%')
                    ->orWhereHas('company', function ($q2) {
                        $q2->where('company_name', 'like', '%' . $this->filter_params->search . '%');
                    });
            });
        }

        // Filter by company if provided
        // if ($this->filter_params?->company) {
        //     $query->where('company_id', $this->filter_params->company);
        // }

        if (
            isset($this->filter_params?->status) &&
            $this->filter_params->status !== '' &&
            $this->filter_params->status !== 'all'
        ) {
            $query->where('status', $this->filter_params->status);
        }
        //  if (Auth::guard('team_person')->check()) {
        //     $query->where('company_id', $this->user->company_id);
        // }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        $headings = ['Sr No', 'Expo Category Name', 'Status'];

        // if (!$this->user || empty($this->user['company_id'])) {
        //     array_splice($headings, 1, 0, 'Company Name'); // Insert after Sr No
        // }

        return $headings;
    }

    public function map($row): array
    {
        $rowData = [
            $this->srNo++,
            $row->name,
            $row->status
        ];
        // if (!$this->user || empty($this->user['company_id'])) {
        //     array_splice($rowData, 1, 0, optional($row->company)->company_name ?? '-');
        // }

        return $rowData;
    }


    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center'],
            ],
        ];
    }
}
