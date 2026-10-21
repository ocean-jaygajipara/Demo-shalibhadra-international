<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseSubCategory;
use App\Models\TeamPerson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;



class ExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(Request $request): array
    {
        $id = $request->route('expense') ?? 0;

        // Get the selected expense subcategory from the request
        $table = (new Expense())->getTable();

        // $expenseSubcategory = ExpenseSubCategory::find($request->expense_subcategory_id);

        // Default values if subcategory doesn't exist
        // $minAmount = $expenseSubcategory ? $expenseSubcategory->min_amount : 0;
        // $maxAmount = $expenseSubcategory ? $expenseSubcategory->max_amount : 9999999;
        // $per_km_rate = $expenseSubcategory ? $expenseSubcategory->per_km_rate : 0;

        $rules = [

            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
            'team_person_id' => [
                'required',
                Rule::exists((new TeamPerson())->getTable(), 'id')
            ],
            'expense_category_id' => [
                'required',
                Rule::exists((new ExpenseCategory())->getTable(), 'id')
            ],
            'expense_subcategory_id' => [
                'required',
                Rule::exists((new ExpenseSubCategory())->getTable(), 'id'),
                // Rule::unique($table)
                //     ->where(function ($query) use ($request) {
                //         return $query->where('company_id', $request->company_id)
                //             ->where('team_person_id', $request->team_person_id)
                //             ->where('expense_category_id', $request->expense_category_id);
                //     })
                //     ->ignore($id),
            ],

            'date' => ['required', 'date', 'date_format:Y-m-d'],

            'req_amount' => ['required', 'numeric', 'min:0'],
            // 'pass_amount' => ['nullable', 'numeric', function ($attribute, $value, $fail) use ($request) {
            //     $expense = Expense::find($request->id);
            //     if ($expense && $value > $expense->pass_amount) {
            //         $fail('The Pass amount cannot be greater than the Request amount.');
            //     }
            // }],
            // 'pass_amount'=>[],
            // 'req_amount' => ['numeric'],
            // 'reason'     => [''],
            'remark' => [
                'nullable',
            ],
            'status' => [
                Rule::in(['pending', 'pass', 'reject']),
            ],
        ];
        $subcategory = ExpenseSubCategory::find($this->input('expense_subcategory_id'));

        if ($subcategory && $subcategory->is_image_required == 1) {
            $rules['image'] = 'required|mimes:jpeg,png,jpg,pdf|max:2048';
        } else {
            $rules['image'] = 'nullable|mimes:jpeg,png,jpg,pdf|max:2048';
        }

        // dd("L-33", $rules, $request->all());
        return $rules;
    }
}
