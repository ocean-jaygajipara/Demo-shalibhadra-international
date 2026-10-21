<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\MarketingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarketingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(Request $request): array
    {
        $id = $request->route('marketing_status') ?? 0;
        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
            'type' => [
                'required',
                'in:Inquiry,Followup',
            ],
            'display_order' => [
                'nullable',
               Rule::unique((new MarketingStatus())->getTable())
                    ->where(function ($query) use ($request, $id) {
                        $query->where('company_id', $request->company_id)
                            ->where('type', $request->type)
                            ->where('id', '!=', $id);
                    }),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new MarketingStatus())->getTable())
                    ->where(function ($query) use ($request, $id) {
                        $query->where('company_id', $request->company_id)
                            ->where('type', $request->type)
                            ->where('id', '!=', $id);
                    }),
            ],
            'status_color' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new MarketingStatus())->getTable())
                    ->where(function ($query) use ($request, $id) {
                        $query->where('company_id', $request->company_id)
                            ->where('type', $request->type)
                            ->where('id', '!=', $id);
                    }),
            ],
            'status_color_2' => [
                'required',
                'string',
                'max:255',
                // Rule::unique((new MarketingStatus())->getTable())
                //     ->where(function ($query) use ($request, $id) {
                //         $query->where('company_id', $request->company_id)
                //             ->where('type', $request->type)
                //             ->where('id', '!=', $id);
                //     }),
            ],
            'text_color' => [
                'required',
                'string',
                'max:255',
                // Rule::unique((new MarketingStatus())->getTable())
                //     ->where(function ($query) use ($request, $id) {
                //         $query->where('company_id', $request->company_id)
                //             ->where('type', $request->type)
                //             ->where('id', '!=', $id);
                //     }),
            ],
            'status' => 'required|in:active,inactive',
            'is_default' => 'nullable|in:0,1',
        ];
        return $rules;
    }

    public function messages()
    {
        return [
            'company_id.required' => 'The company is required.',
            'type.required' => 'The type is required.',
            'name.required' => 'The name is required.',
            'name.unique' => 'The name must be unique.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
