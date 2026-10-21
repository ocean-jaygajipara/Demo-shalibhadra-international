<?php

namespace App\Exports;

use App\Models\ExpenseSubCategory;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExpenseSubCategoryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
        $query = ExpenseSubCategory::with(['expense_category', 'team_person', 'company']);


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

        if (
            isset($this->filter_params->status) &&
            $this->filter_params->status !== '' &&
            $this->filter_params->status !== 'all'
        ) {
            $query->where('status', $this->filter_params->status);
        }

        if (!empty($this->filter_params->team_person_ids)) {
            $query->whereHas('team_person', function ($q) {
                $q->whereRaw("FIND_IN_SET(?, team_person_ids)", [$this->filter_params->team_person_ids]);
            });
        }

        if (!empty($this->filter_params->company)) {
            $query->where('company_id', $this->filter_params->company);
        }


        if (!empty($this->filter_params->expense_category)) {
            $query->where('expense_category_id', $this->filter_params->expense_category);
        }
        if (Auth::guard('team_person')->check()) {
            $query->where('company_id', $this->user->company_id);
        }
        // $query->where('company_id', $this->user->company_id);

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
            'Expense Details',
            'Team Person Name',
            'Status',
        ];

        if (!$this->user || empty($this->user['company_id'])) {
            array_splice($headings, 1, 0, 'Company Name'); // insert before Status
        }

        return $headings;
    }

    public function map($expenseSubCategory): array
    {
        $expenseDetails = '-';
        if ($expenseSubCategory->expense_type === 'General') {
            $expenseDetails = 'Min: ' . $expenseSubCategory->min_amount . ', Max: ' . $expenseSubCategory->max_amount;
        } elseif ($expenseSubCategory->expense_type === 'KM') {
            $expenseDetails = 'Per KM Rate: ' . $expenseSubCategory->per_km_rate;
        } elseif ($expenseSubCategory->expense_type === 'Food') {
            $expenseDetails = 'Fix Amount: ' . $expenseSubCategory->fix_amount .
                ', From: ' . $expenseSubCategory->from_time .
                ', To: ' . $expenseSubCategory->to_time;
        }

        $row = [
            $this->srNo++,
            $expenseSubCategory->name,
            optional($expenseSubCategory->expense_category)->name ?? '-',
            $expenseDetails,
            $row = $expenseSubCategory->teamPersons->pluck('name')->implode(', ') ?: '-',
            ucfirst($expenseSubCategory->status)
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
