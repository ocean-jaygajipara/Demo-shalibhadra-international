<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ExpenseCategory;
use App\Models\ExpenseSubCategory;

class TinkerExpense extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tinker:expense';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old ExpenseCategory and ExpenseSubCategory records, and add new ones';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        /**----------------Expense Category ----------------------------- */

        // Delete old ExpenseCategory records
        $this->info('Deleting old ExpenseCategory records...');
        ExpenseCategory::truncate();

        // Create new ExpenseCategory records with company_id between 1 and 5
        $this->info('Creating new ExpenseCategory records...');
        \Database\Factories\ExpenseCategoryFactory::new()->count(5)->create();

        $this->info('5 new ExpenseCategory records created successfully!');

        /**----------------Expense Sub Category ----------------------------- */

        // Delete old ExpenseSubCategory records
        $this->info('Deleting old ExpenseSubCategory records...');
        ExpenseSubCategory::truncate();

        // Create new ExpenseSubCategory records related to the newly created ExpenseCategories
        $this->info('Creating new ExpenseSubCategory records...');
        \Database\Factories\ExpenseSubCategoryFactory::new()->count(5)->create();

        $this->info('5 new ExpenseSubCategory records created successfully!');
    }
}
