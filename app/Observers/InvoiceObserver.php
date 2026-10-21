<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\SalesStatus;

class InvoiceObserver
{
    /**
     * Handle the Invoice "saving" event.
     * Generate invoice number when status is being set/changed to "Approve"
     */
    public function saving(Invoice $invoice)
    {
        // Only generate if invoice_order_no is empty and status is being set/changed to "Approve"
        if (empty($invoice->invoice_order_no) && $invoice->isDirty('sales_status_id')) {
            // Get the new sales status
            $newStatus = SalesStatus::find($invoice->sales_status_id);
            
            // If status slug is 'app' (Approve), generate invoice number
            if ($newStatus && $newStatus->statusslug === 'app') {
                // Load company relationship if not loaded (needed for generateInvoiceNumber)
                if (!$invoice->relationLoaded('company')) {
                    $invoice->load('company');
                }
                
                // Load sales_status relationship if not loaded
                if (!$invoice->relationLoaded('sales_status')) {
                    $invoice->load('sales_status');
                }
                
                // Generate invoice number
                $invoiceNumber = $invoice->generateInvoiceNumber();
                if ($invoiceNumber) {
                    $invoice->invoice_order_no = $invoiceNumber;
                }
            }
        }
    }
}

