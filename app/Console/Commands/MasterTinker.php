<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MasterUnit;
use App\Models\MasterWarehouse;
use App\Models\MasterTransportBy;
use App\Models\MasterTransportDetail;
use App\Models\DocumentType;
use App\Models\DocumentList;
use App\Models\Designation;
use App\Models\SalesStatus;
use App\Models\FollowUpReason;
use App\Models\MarketingStatus;
use App\Models\ProductVariant;
use App\Models\Company;


class MasterTinker extends Command
{
    // The name and signature of the console command.
    protected $signature = 'tinker:masters';

    // The console command description.
    protected $description = 'Insert 5 new MasterUnit and MasterWarehouse records using factories';

    // Execute the console command.
    public function handle()
    {

        /*-----------------Master Unit Tinker Call-------------------------*/
        MasterUnit::truncate(); // Remove all records from master_unit table
        MasterUnit::factory()->count(2)->create(); // Insert 5 new MasterUnit records

        /*-----------------Master Warehouse Tinker Call-------------------------*/
        MasterWarehouse::truncate(); // Remove all records from master_warehouse table
        MasterWarehouse::factory()->count(5)->create(); // Insert 5 new MasterWarehouse records

        /*-----------------Master Transpport By Tinker Call-------------------------*/ 
        MasterTransportBy::truncate();
        MasterTransportBy::factory()->count(5)->create();

        /*-----------------Master Transpport Details Tinker Call-------------------------*/
        MasterTransportDetail::truncate();
        MasterTransportDetail::factory()->count(5)->create();


        /*----------------- Document Type Details Tinker Call-------------------------*/
        DocumentType::truncate();
        DocumentType::factory()->count(5)->create();


        /*----------------- Document List Details Tinker Call-------------------------*/
        DocumentList::truncate();
        DocumentList::factory()->count(5)->create();


        /*----------------- Designation Details Tinker Call-------------------------*/
        Designation::truncate();
        Designation::factory()->count(5)->create();

        /*----------------- Sales Status Details Tinker Call-------------------------*/
        SalesStatus::truncate();
        SalesStatus::factory()->count(5)->create();

        /*----------------- FollowUpReason Tinker Call -------------------------*/
        FollowUpReason::truncate(); // Clear existing data
        FollowUpReason::factory()->count(5)->create(); // Seed new data

        /*----------------- Truncate and Seed MarketingStatus -------------------------*/
        MarketingStatus::truncate(); // Clear existing data
        MarketingStatus::factory()->count(5)->create(); // Seed 5 new records



        ProductVariant::truncate(); // This will delete all rows in the product_variant table

        $this->info('✅ Old product variants removed.');

        // Fetch all companies
        $companies = Company::all();

        // Add new product variants for each company
        foreach ($companies as $company) {
            // Create 2 new product variants for each company using the updated factory
            \App\Models\ProductVariant::factory()->count(2)->create([
                'company_id' => $company->id,
            ]);

            $this->info("✅ Created 2 new product variants for Company ID: {$company->id}");
        }

        $this->info("✅Masters Record Added Succuess Fully.");

        /*----------------- TeamRole Tinker Call -------------------------*/
        \App\Models\TeamRole::truncate(); // Clear existing data

        foreach ($companies as $company) {
            // Add default Super Admin role
            \App\Models\TeamRole::create([
                'company_id' => $company->id,
                'parent_id' => 0,
                'name' => 'Super Admin',
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
            ]);

            // Add one company-specific role
            \App\Models\TeamRole::create([
                'company_id' => $company->id,
                'parent_id' => 1, // Assuming '1' refers to Super Admin or use actual ID if needed
                'name' => $company->name . ' Role',
                'status' => 'active',
                'created_by' => 1,
                'updated_by' => 1,
                'deleted_by' => null,
            ]);

            $this->info("✅ Created roles for Company ID: {$company->id} ({$company->name})");
        }
    }
}
