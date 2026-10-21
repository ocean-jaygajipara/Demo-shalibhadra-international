<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PlanMaster;
use App\Models\MasterCountry;
use App\Models\MasterState;
use App\Models\MasterCity;
use App\Models\MasterArea;
use App\Models\Company;
use Illuminate\Support\Facades\Artisan;

class TinkerWithPlanData extends Command
{
    protected $signature = 'tinker:plan';
    protected $description = 'Seed PlanMaster, Country, State, City, Area, and Company data with random counts.';

    public function handle()
    {
        /** Migration Run  */
        $this->info('Migration Fresh Run');
        Artisan::call("migrate:fresh");
        $this->info('Migration Fresh Complete');

        /** Tinker Run  */
        // Delete existing PlanMaster records
        $this->info('Deleting existing PlanMaster records...');
        PlanMaster::truncate();

        // Create dummy PlanMaster records
        $this->info('Creating dummy PlanMaster data...');
        PlanMaster::factory()->count(2)->create();

        /*--------------------------------------------------------------------------------------*/
        // Delete existing location data
        $this->info('Deleting existing MasterCountry, MasterState, MasterCity, and MasterArea records...');

        // Delete from bottom to top of the hierarchy
        MasterArea::truncate();
        MasterCity::truncate();
        MasterState::truncate();
        MasterCountry::truncate();

        // Create new countries
        $this->info('Creating dummy MasterCountry data...');
        $countries = MasterCountry::factory()->count(5)->create();

        foreach ($countries as $country) {
            // $this->info("Creating states for country: {$country->name}");
            $stateCount = rand(3, 6);
            $states = MasterState::factory()->count($stateCount)->create([
                'country_id' => $country->id,
            ]);

            foreach ($states as $state) {
                // $this->info("Creating cities for state: {$state->name}");
                $cityCount = rand(3, 6);
                $cities = MasterCity::factory()->count($cityCount)->create([
                    'country_id' => $country->id,
                    'state_id' => $state->id,
                ]);

                /*
                foreach ($cities as $city) {
                    // $this->info("Creating areas for city: {$city->name}");
                    $areaCount = rand(3, 8);
                    MasterArea::factory()->count($areaCount)->create([
                        'company_id' => "1",
                        'country_id' => $country->id,
                        'state_id' => $state->id,
                        'city_id' => $city->id,
                    ]);
                }
                */
            }
        }

        /*--------------------------------------------------------------------------------------*/
        // Delete and create Company data
        $this->info('Deleting existing Company records...');
        Company::truncate();

        $this->info('Creating 5 dummy Company records...');
        \Database\Factories\CompanyFactory::new()->count(3)->create();

        // Done
        $this->info('✔ All data added successfully.');

        /** DB Seeder Call  */

        Artisan::call("db:seed");
        $this->info('Seeder run complete');

        $this->info('Press Enter for creae the API Token');

        /** Passport client create */
        Artisan::call("passport:client --personal");
        $this->info('Passport client create successfully.');

        Artisan::call("tinker:create-teamperson");
        $this->info('Team Person Added Complete');

        Artisan::call("tinker:masters");
        $this->info('Masters Data Added Done');

        Artisan::call("tinker:expense");
        $this->info('Expense Run Complete');

        Artisan::call("generate:menu-map");
        $this->info('Complet Generate menu map');

        Artisan::call("producttinker");
        $this->info('Product Tinker Run Complete');
    }
}
