<?php

namespace Database\Factories;

use App\Models\DocumentList;
use App\Models\Company;
use App\Models\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentListFactory extends Factory
{
    protected $model = DocumentList::class;

    public function definition()
    {
        // List of standard industry names for document types
        $documentNames = [
            'Identity Proof',
            'Address Proof',
            'Business Registration',
            'Tax Identification Number (TIN)',
            'Passport',
            'Driver’s License',
            'Bank Statement',
            'Utility Bill',
            'Work Permit',
            'Contract Agreement',
            'Non-Disclosure Agreement (NDA)',
            'Employee Handbook',
            'Invoice',
            'Purchase Order',
            'Lease Agreement',
        ];

        return [
            'company_id' => Company::inRandomOrder()->first()?->id ?? 1,
            'document_type_id' => DocumentType::inRandomOrder()->first()?->id ?? 1,
            'name' => $this->faker->randomElement($documentNames), // Randomly choose from standard document names
            'status' => 'active',
            'image' => $this->faker->imageUrl(640, 480, 'documents', true, 'Doc'), // Simulated image URL
            'created_by' => $this->faker->numberBetween(1, 5),
            'updated_by' => $this->faker->numberBetween(1, 5),
            'deleted_by' => null,
        ];
    }
}
