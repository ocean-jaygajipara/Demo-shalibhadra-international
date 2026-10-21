<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobWorkIssue;
use App\Jobs\GenerateJobWorkIssuePdfJob;

class GenerateJobWorkIssuePdfs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:job-work-issue-pdfs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate PDFs for all existing Job Work Issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jobIssues = JobWorkIssue::withTrashed()->get();
        $this->info("Starting PDF generation for " . $jobIssues->count() . " Job Work Issues...");

        $bar = $this->output->createProgressBar($jobIssues->count());
        $bar->start();

        foreach ($jobIssues as $jobIssue) {
            GenerateJobWorkIssuePdfJob::dispatch($jobIssue->id);
            $bar->advance();
        }

        $bar->finish();
        $this->info("\nAll jobs dispatched to queue.");
    }
}
