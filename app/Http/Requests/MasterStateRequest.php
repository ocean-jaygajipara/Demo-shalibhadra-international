<?php

namespace App\Http\Requests;

use App\Models\MasterState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MasterStateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(Request $request): array
    {
        $id = $request->route('master_state') ?? 0;
        $table = (new MasterState())->getTable();

        $rules = [
            'country_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique((new MasterState())->getTable())
                    ->where('name', $request->name)
                    ->whereNull('deleted_at')
                    ->ignore($id),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique($table)
                    ->where(function ($query) use ($request) {
                        return $query->where('country_id', $request->country_id)
                            ->whereNull('deleted_at');
                    })
                    ->ignore($id),
            ],
            'status' => [
                'required',
                Rule::in(['active', 'inactive']),
            ],
        ];
        // dd("L-48", $rules, $id, $request->all(), $request->route('master-state'), $request->route()->parameters());
        return $rules;
    }
}
