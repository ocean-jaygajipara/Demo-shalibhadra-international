<?php

namespace App\Exports;

use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpenseCategoryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
        $query = ExpenseCategory::with('company');

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
                $q->where('name', 'like', '%' . $this->filter_params->search . '%')
                    ->orWhereHas('company', function ($q2) {
                        $q2->where('company_name', 'like', '%' . $this->filter_params->search . '%');
                    });
            });
        }


        if ($this->filter_params?->status !== null && $this->filter_params->status !== '' && $this->filter_params->status !== 'all') {
            $query->where('status', $this->filter_params->status);
        }

        if ($this->filter_params?->company) {
            $query->where('company_id', $this->filter_params->company);
        }
        if (Auth::guard('team_person')->check()) {
            $query->where('company_id', $this->user->company_id);
        }

        // $sql = $query->toSql();

        // $bindings = $query->getBindings();

        // foreach ($bindings as $binding) {

        //     $sql = preg_replace('/\?/', is_numeric($binding) ? $binding : "'{$binding}'", $sql, 1);
        // }

        // dd($sql);
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
            'Expense Category Name',
            'Status',
        ];
        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name'); // Insert before Status
        }

        return $headings;
    }

    public function map($expenseCategory): array
    {
        $row = [
            $this->srNo++,
            // optional($expenseCategory->company)->company_name ?? '-',
            $expenseCategory->name,
            ucfirst($expenseCategory->status)
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($row, 1, 0, optional($expenseCategory->company)->company_name ?? '-');
        }

        return $row;
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
