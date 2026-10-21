<?php

namespace App\Http\Requests;

use App\Models\Inquiry;
use App\Models\Company;
use App\Models\MarketingStatus;
use App\Models\FollowUpReason;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FollowupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(Request $request): array
    {
        $id = $request->route('designation') ?? 0;
        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
            'inquiry_id' => [
                'required',
                Rule::exists((new Inquiry())->getTable(), 'id')
            ],
            'reason_id' => [
                'required',
                Rule::exists((new FollowUpReason())->getTable(), 'id')
            ],
            'status_id' => [
                'required',
                Rule::exists((new MarketingStatus())->getTable(), 'id')
            ],
            'date_time' => [
                'required',
                // 'date_format:d-m-Y h:i A',
                function ($attribute, $value, $fail) {
                    try {
                        $date = \Carbon\Carbon::createFromFormat('d-m-Y h:i A', $value);
                        $now = \Carbon\Carbon::now()->subMinutes(30);

                        if ($date->lte($now)) {
                            $fail("The $attribute must be a future date and time (after now).");
                        }
                    } catch (\Exception $e) {
                        $fail("The $attribute is not a valid date and time.");
                    }
                },
                /*
                'date_format:d/m/Y h:i A',
                function ($attribute, $value, $fail) {
                    try {
                        $value = trim($value); // Trim input to remove hidden spaces
                        $date = \Carbon\Carbon::createFromFormat('d/m/Y h:i A', $value);
                        $currentDateTime = \Carbon\Carbon::now();
                        if ($date === false || $date->format('d/m/Y h:i A') !== $value) {
                            // Strict format check
                            $fail("The $attribute is not a valid date and time format.");
                            return;
                        }
                        if ($date->lte($currentDateTime)) {
                            $fail("The $attribute must be a date and time after the current moment (" . $currentDateTime->format('d/m/Y h:i A') . ").");
                        }
                    } catch (\Exception $e) {
                        $fail("The $attribute is not a valid date and time.");
                    }
                },*/
            ],
            'details' => [
                'required',
            ],
            'through' => [
                'required',
                'in:Call,SMS,Email',
            ]
        ];
        // dd("Validation 85", $rules, $request->all(), $request['date_time'], \Carbon\Carbon::now());
        return $rules;
    }

    public function messages()
    {
        return [
            'company_id.required' => 'The company name is required.',
            'inquiry_id.required' => 'The inquiry name is required.',
            'reason_id.required' => 'The reason name is required.',
            'status_id.required' => 'The status is required.',
            'date_time.required' => 'The date time is required.',
            'details.required' => 'The details is required.',
            'through.required' => 'The through is required.',
            'through.in' => 'The through must be either Call, SMS or Email.',
        ];
    }
}
