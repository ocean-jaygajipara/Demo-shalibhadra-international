<?php

namespace App\Exports;

use App\Models\FollowUpReason;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Auth;

class FollowUpReasonExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected $srNo = 1;
    protected $filter_params;
    protected $user;
    public function __construct($filter_params = null, $user = null)
    {
        $this->filter_params = (object) $filter_params;
        $this->user = $user;
    }
    public function collection()
    {
        $query = FollowUpReason::with('company');

        // Apply search filter
        if (isset($this->filter_params->search) && $this->filter_params->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->filter_params->search . '%')
                    ->orWhereHas('company', function ($q2) {
                        $q2->where('company_name', 'like', '%' . $this->filter_params->search . '%');
                    });
                // ->orWhereHas('ProductCategory', function ($q3) {
                //     $q3->where('name', 'like', '%' . $this->filter_params->search . '%');
                // });
            });
        }

        if (Auth::guard('team_person')->check()) {
            $teamPersonCompanyId = Auth::guard('team_person')->user()->company_id;
            $query->where('company_id', $teamPersonCompanyId);
        }
        // Filter by company
        if ($this->filter_params?->company) {
            $query->where('company_id', $this->filter_params->company);
        }


        // Filter by status
        if (
            isset($this->filter_params?->status) &&
            $this->filter_params->status !== '' &&
            $this->filter_params->status !== 'all'
        ) {
            $query->where('status', $this->filter_params->status);
        }

        return $query->latest()->get();
    }

    public function headings(): array
    {
        $headings =  [
            'Sr',
            'Name',
            'Status',
        ];
        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name'); // insert after Sr No
        }

        return $headings;
    }

    public function map($row): array
    {


        // Basic row data
        $rowData = [
            $this->srNo++,
            $row->name,
            $row->status
        ];

        // If user is not limited to a company, insert company name after Sr No
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
        return 'FollowUp Reasons';
    }
}
