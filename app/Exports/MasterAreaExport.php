<?php

namespace App\Exports;

use App\Models\MasterArea;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithStyles;


class MasterAreaExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $srNo = 0;
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
        $query = MasterArea::with(['company', 'country', 'state', 'city'])->whereNull('deleted_at');
        if (!empty($this->filter_params->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        } else if ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user?->company_id);
        }
        // Apply search filter
        if (!empty($this->filter_params->search)) {
            $query->where(function ($q) {
                $q->where('area_name', 'like', '%' . $this->filter_params->search . '%')
                    ->orWhereHas('company', function ($q2) {
                        $q2->where('company_name', 'like', '%' . $this->filter_params->search . '%');
                    });
            });
        }

        // Filter by company
        if (!empty($this->filter_params->company)) {
            $query->where('company_id', $this->filter_params->company);
        }

        // Filter by country
        if (!empty($this->filter_params->filter_country)) {
            $query->where('country_id', $this->filter_params->filter_country);
        }

        // Filter by state
        if (!empty($this->filter_params->filter_state)) {
            $query->where('state_id', $this->filter_params->filter_state);
        }

        // Filter by city
        if (!empty($this->filter_params->filter_city)) {
            $query->where('city_id', $this->filter_params->filter_city);
        }

        // Filter by status
        if (isset($this->filter_params->status) && $this->filter_params->status !== '' && $this->filter_params->status !== 'all') {
            $query->where('status', $this->filter_params->status);
        }

        // Restrict by logged-in team_person user’s company
        if (Auth::guard('team_person')->check()) {
            $query->where('company_id', $this->user->company_id);
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
            'Country Name',
            'State Name',
            'City Name',
            'Area Name',
            'Status',
        ];

        if (!$this->user || empty($this->user->company_id)) {
            array_splice($headings, 1, 0, 'Company Name');
        }

        return $headings;
    }

    public function map($row): array
    {
        $rowData = [
            ++$this->srNo,
            optional($row->country)->name ?? 'N/A',
            optional($row->state)->name ?? 'N/A',
            optional($row->city)->name ?? 'N/A',
            $row->area_name,
            ucfirst($row->status),
        ];

        if (!$this->user || empty($this->user->company_id)) {
            array_splice($rowData, 1, 0, optional($row->company)->company_name ?? '-');
        }

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
