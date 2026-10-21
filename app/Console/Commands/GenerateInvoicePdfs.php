<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Jobs\GenerateInvoicePdfJob;

class GenerateInvoicePdfs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:invoice-pdfs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate PDFs for all existing Invoices';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $invoices = Invoice::withTrashed()->get();
        $this->info("Starting PDF generation for " . $invoices->count() . " Invoices...");

        $bar = $this->output->createProgressBar($invoices->count());
        $bar->start();

        foreach ($invoices as $invoice) {
            GenerateInvoicePdfJob::dispatchSync($invoice->id);
            $bar->advance();
        }

        $bar->finish();
        $this->info("\nAll jobs dispatched to queue.");
    }
}
