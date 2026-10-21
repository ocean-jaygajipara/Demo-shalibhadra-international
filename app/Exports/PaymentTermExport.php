<?php

namespace App\Exports;

use App\Models\PaymentTerm;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentTermExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
        $query = PaymentTerm::with('company');

        // Search filter
        if ($this->filter_params?->search) {
            $query->where(function ($q) {
                $q->where('payment_terms', 'like', '%' . $this->filter_params->search . '%')
                    ->orWhere('credit_days', 'like', '%' . $this->filter_params->search . '%')
                    ->orWhereHas('company', function ($q2) {
                        $q2->where('company_name', 'like', '%' . $this->filter_params->search . '%');
                    });
            });
        }

        // Company filter
        if (!empty($this->filter_params->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        } elseif ($this->user && $this->user?->company_id) {
            $query->where('company_id', $this->user->company_id);
        }

        // Filter by status
        if (
            isset($this->filter_params?->status) &&
            $this->filter_params->status !== '' &&
            $this->filter_params->status !== 'all'
        ) {
            $query->where('status', $this->filter_params->status);
        }

        // Auth guard (team_person)
        if (Auth::guard('team_person')->check()) {
            $query->where('company_id', $this->user->company_id);
        }

        // Filter by created_by
        if (!empty($this->filter_params->filter_created_by)) {
            $query->where('created_by', $this->filter_params->filter_created_by);
        } elseif (
            $this->user &&
            $this->user->company_id &&
            empty($this->modules['all_data_permission'])
        ) {
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
            'Payment Term',
            'Credit Days',
            'Status',
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name'); // Insert after Sr No
        }

        return $headings;
    }

    public function map($row): array
    {
        $data = [
            $this->srNo++,
            $row->payment_terms,
            $row->credit_days,
            $row->status,
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($data, 1, 0, optional($row->company)->company_name ?? '-'); // Insert company name after Sr No
        }

        return $data;
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
