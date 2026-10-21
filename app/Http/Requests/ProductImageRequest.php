<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductImageRequest extends FormRequest
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
        $id = $request->route('product_image') ?? 0;

        // Define common rules
        $rules = [
            'company_id' => [
                'required',
                Rule::exists((new Company())->getTable(), 'id')
            ],
            'product_id' => [
                'required',
                Rule::exists((new Product())->getTable(), 'id')
            ],
            'image_name' => [
                'required',
                'string',
                'max:255',
            ],
            'status' => [
                'required',
                'in:active,inactive',
            ],
        ];


        if (!$id) {
            $rules['image_path'] = [
                'required',
                'max:51200',
            ];
        } else {
            $rules['image_path'] = [
                'nullable',
                'max:51200',
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'company_id.required' => 'The company is required.',
            'product_id.required' => 'The product is required.',
            'image_name.required' => 'The image name is required.',
            'image_name.string' => 'The image name must be a string.',
            'image_name.max' => 'The image name should not exceed 255 characters.',
            'image_path.required' => 'The image path is required.',
            'image_path.string' => 'The image path must be a string.',
            'image_path.max' => 'The image path should not exceed 255 characters.',
            'image_path.unique' => 'This image path already exists.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be either active or inactive.',
        ];
    }
}
