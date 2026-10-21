<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\PaymentReceipt;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\TeamPerson;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;


class PaymentReceiptRequest extends FormRequest
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
     */
    public function rules(Request $request): array
    {
        $id = $request->route('payment_receipt') ?? 0;
        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id'),
            ],
            'date' => [
                'required',
                'date_format:d-m-Y'
            ],
            'customer_type_id' => [
                'required',
                Rule::exists((new CustomerType())->getTable(), 'id'),
            ],
            'customer_id' => [
                'required',
                Rule::exists((new Customer())->getTable(), 'id'),

            ],
             'quotation_id' => [
                'nullable',
                Rule::exists((new Quotation())->getTable(), 'id'),
            ],
             'sales_order_id' => [
                'nullable',
                Rule::exists((new SalesOrder())->getTable(), 'id'),
            ],
            'receipt_type' => [
                'required',
                'string',
                'max:255'
            ],
             'receipt_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new PaymentReceipt())->getTable())->where(function ($query) use ($request) {
                    return $query->where('company_id', $request->company_id)
                            ->whereNull('deleted_at');
                })->ignore($id),
            ],
            'team_person_ids' => [
                'required',
                Rule::exists((new TeamPerson())->getTable(), 'id'),
            ],

            'payment_by' => [
                'required',
                'string',
                'max:255'
            ],
            'cheque_no' => [
                'required_if:payment_by,cheque'
            ],
            'upi_no' => [
                'required_if:payment_by,upi'
            ],
            'payment_reason' => [
                'required_if:payment_by,other'
            ],
            'payment_mode' => [
                'required',
                'string',
                'max:100'
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0'
            ],
            'remark' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'status' => [
                // 'required',
                'in:pending,approve'
            ],
        ];

        $receiptType = $request->input('receipt_type');
        if ($receiptType === 'quotation') {
            $rules['quotation_id'] = [
                'required',
                Rule::exists((new Quotation())->getTable(), 'id'),
            ];
        }

        if ($receiptType === 'order') {
            $rules['sales_order_id'] = [
                'required',
                Rule::exists((new SalesOrder())->getTable(), 'id'),
            ];
        }
        return $rules;
    }

    /**
     * Custom error messages.
     */
}
