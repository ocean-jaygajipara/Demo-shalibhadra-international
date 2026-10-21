<?php

namespace Database\Factories;

use App\Models\DocumentType;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentTypeFactory extends Factory
{
    protected $model = DocumentType::class;

    public function definition()
    {
        $documentNames = [
            'PAN Card',
            'Aadhar Card',
            'GST Certificate',
            'Incorporation Certificate',
            'Rental Agreement',
            'Cancelled Cheque',
            'Passport',
            'Driving License',
            'Trade License',
            'Electricity Bill',
        ];

        return [
            'company_id' => Company::inRandomOrder()->first()->id,
            'name' => $this->faker->unique()->randomElement($documentNames),
            'status' => 'active',
            'created_by' => $this->faker->numberBetween(1, 5),
            'updated_by' => $this->faker->numberBetween(1, 5),
            'deleted_by' => null,
        ];
    }
}
