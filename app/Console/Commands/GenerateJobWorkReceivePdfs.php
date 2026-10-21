<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Jobs\GenerateJobWorkReceivePdfJob;
use App\Models\JobWorkReceive;

class GenerateJobWorkReceivePdfs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:job-work-receive-pdfs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate PDFs for all existing Job Work Receives';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jobReceives = JobWorkReceive::withTrashed()->get();
        $this->info("Starting PDF generation for " . $jobReceives->count() . " Job Work Receives...");

        $bar = $this->output->createProgressBar($jobReceives->count());
        $bar->start();

        foreach ($jobReceives as $jobReceive) {
            GenerateJobWorkReceivePdfJob::dispatch($jobReceive->id);
            $bar->advance();
        }

        $bar->finish();
        $this->info("\nAll jobs dispatched to queue.");
    }
}
