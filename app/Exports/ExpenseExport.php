<?php

namespace App\Exports;

use App\Models\Expense;
use App\Models\ExpenseSubCategory;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpenseExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
        $query = Expense::with(['expense_sub_category', 'expense_category', 'team_person', 'company']);
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

        if (isset($this->filter_params->status) && $this->filter_params->status !== '' && $this->filter_params->status !== 'all') {
            $query->where('status', $this->filter_params->status);
        }

        if (!empty($this->filter_params->filter_company)) {
            $query->where('company_id', $this->filter_params->filter_company);
        }

        if (!empty($this->filter_params->filter_expense_category)) {
            $query->where('expense_category_id', $this->filter_params->filter_expense_category);
        }

        if (!empty($this->filter_params->filter_expense_subcategory_id)) {
            $query->where('expense_subcategory_id', $this->filter_params->filter_expense_subcategory_id);
        }
        if (!empty($this->filter_params->filter_team_person_id)) {
            $query->where('team_person_id', $this->filter_params->filter_team_person_id);
        }
        if (!empty($this->filter_params->from_date) && !empty($this->filter_params->from_date) && !empty($this->filter_params->to_date)) {
            $query->whereBetween('date', [
                $this->filter_params->from_date,
                $this->filter_params->to_date
            ]);
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
            'Expense Sub Category Name',
            'Expense Category Name',
            'Team Person Name',
            'Date',
            'Requested Amount',
            'Passed Amount',
            'Status',
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name'); // Insert before Status
        }

        return $headings;
    }

    public function map($expenseSubCategory): array
    {

        $row = [
            $this->srNo++,
            optional($expenseSubCategory->expense_sub_category)->name ?? '-',
            optional($expenseSubCategory->expense_category)->name ?? '-',
            optional($expenseSubCategory->team_person)->name ?? '-',
            $expenseSubCategory->date ? \Carbon\Carbon::parse($expenseSubCategory->date)->format('d-m-Y') : '-',
            $expenseSubCategory->req_amount ?? '0.00',
            $expenseSubCategory->pass_amount ?? '0.00',
            $expenseSubCategory->status === 'pass' ? 'Pass' : ($expenseSubCategory->status === 'reject' ? 'Reject' : 'Pending')


        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($row, 1, 0, optional($expenseSubCategory->company)->company_name ?? '-');
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
