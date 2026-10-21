<?php

namespace App\Exports;

use App\Models\TermsAndCondition;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TermsAndConditionExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
        $query = TermsAndCondition::with('company');

        // Filter by selected company from filters (takes priority)
        if (!empty($this->filter_params->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        }
        // Else if user has a company assigned, restrict by it
        else if ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user->company_id);
        }

        // Team person guard (if authenticated)
        if (Auth::guard('team_person')->check()) {
            $query->where('company_id', $this->user->company_id);
        }

        // Apply search on title
        if (!empty($this->filter_params->search)) {
            $query->where('title', 'like', "%" . $this->filter_params->search . "%");
        }

        // Apply another company filter if exists (secondary condition, optional)
        if (!empty($this->filter_params->company)) {
            $query->where('company_id', $this->filter_params->company);
        }

        // Status filter
        if (
            isset($this->filter_params->status) &&
            $this->filter_params->status !== '' &&
            $this->filter_params->status !== 'all'
        ) {
            $query->where('status', $this->filter_params->status);
        }

        // Created By (filter or permissions)
        if (!empty($this->filter_params->filter_created_by)) {
            $query->where('created_by', $this->filter_params->filter_created_by);
        } elseif ($this->user && $this->user->company_id && empty($this->modules['all_data_permission'])) {
            // If no all data permission
            if (!empty($this->modules['personal_data_permission'])) {
                // If only personal permission is enabled
                $query->where('created_by', $this->user->id);
            }
        }

        return $query->latest()->get();
    }


    public function headings(): array
    {
        $headings =  [
            'Sr No',
            'Title',
            'Status',
        ];
        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name'); // insert after Sr No
        }
        return $headings;
    }

    public function map($termsAndCondition): array
    {
        $rows = [
            $this->srNo++,
            $termsAndCondition->title ?? '-',
            $termsAndCondition->status ? 'Active' : 'Inactive',
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($rows, 1, 0, optional($termsAndCondition->company)->company_name ?? '-');
        }

        return $rows;
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
