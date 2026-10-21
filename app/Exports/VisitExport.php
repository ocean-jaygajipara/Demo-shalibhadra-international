<?php

namespace App\Exports;

use App\Models\Visit;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VisitExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected $srNo = 1;
    protected $filters;
    protected $user;


    protected $modules;
    public function __construct($filters = [], $user = null, $modules = [])
    {

        $this->filters = $filters;
        $this->user = $user;
        $this->modules = $modules;
    }

    public function collection()
    {
        $query = Visit::with([
            'company',
            'teamPerson',
            'customer',
        ])->latest();

        if (!empty($this->filters['search'])) {
            $query->where(function ($q) {
                $q->whereHas('customer', function ($q2) {
                    $q2->where('customer_name', 'like', '%' . $this->filters['search'] . '%');
                })->orWhere('meet_person_name', 'like', '%' . $this->filters['search'] . '%');
            });
        }

        if (!empty($this->filters['filter_company'])) {
            $query->where('company_id', $this->filters['filter_company']);
        } else if ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user?->company_id);
        }
        if (!empty($this->filters['filter_company'])) {
            $query->where('company_id', $this->filters['filter_company']);
        }

        if (!empty($this->filters['filter_team_person_name'])) {
            $query->where('team_person_id', $this->filters['filter_team_person_name']);
        }

        if (!empty($this->filters['filter_date'])) {
            $query->whereDate('created_at', $this->filters['filter_date']);
        }
        if (!empty($this->filters['filter_created_by'])) {
            $query->where('created_by', $this->filters['filter_created_by']);
        } elseif ($this->user && $this->user->company_id && empty($this->modules['all_data_permission'])) {
            if (!empty($this->modules['personal_data_permission'])) {
                $query->where('created_by', $this->user->id);
            }
        }
        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Sr No',
            'Company Name',
            'Team Person Name',
            'Meet Person Name',
            'Date & Time',
            'Start Remark',
            'Start Address',
            'Start Image',
            'Customer Name',
            'Stop Remark',
            'Stop Address',
            'Stop Image',
        ];
    }

    public function map($row): array
    {
        return [
            $this->srNo++,
            optional($row->company)->company_name,
            optional($row->teamPerson)->name,
            $row->meet_person_name,
            // $row->date_time ? $row->visit_start_date->format('Y-m-d H:i') : '',
            $row->visit_start_date ? \Carbon\Carbon::parse($row->visit_start_date)->format('d-m-Y H:i') : '',
            $row->visit_start_remark,
            $row->visit_start_address,
            $row->start_image,
            optional($row->customer)->customer_name,
            $row->visit_stop_remark,
            $row->visit_stop_address,
            $row->stop_image,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ],
        ];
    }

    public function title(): string
    {
        return 'Visits';
    }
}
