<?php

namespace App\Jobs;
use App\Models\PurchaseOrder;
use App\Helpers\Helper;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SendPurchaseOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $purchase_order_id;
    protected $action;
    protected $oldStatus;
    protected $newStatus;
    public function __construct($purchase_order_id, $action, $oldStatus = null, $newStatus = null)
    {
        $this->purchase_order_id = $purchase_order_id;
        $this->action = $action;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        //dd($this->purchase_order_id,$this->action,$this->oldStatus,$this->newStatus)
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $PurchaseOrder = PurchaseOrder::with(['purchase_order_items','vendor','company', 'team_person','admin_person','transport_by','transport_detail'])->withTrashed()->findOrFail($this->purchase_order_id);
        //dd($PurchaseOrder);

        if (optional($PurchaseOrder->company)->id && !empty(optional($PurchaseOrder->vendor)->email_id)) {

            try {

                $data = [
                    'customer_name'     => $PurchaseOrder->vendor->vendor_name ?? '',
                    'purchase_order_no'      => $PurchaseOrder->purchase_order_no ?? '',
                    'purchase_order_date'    => $PurchaseOrder->purchase_order_date ?? '',
                    'company_name'      => $PurchaseOrder->company->company_name ?? '',
                    'customer_address'  => $PurchaseOrder->address ?? '',
                    'customer_phone'    => $PurchaseOrder->phone ?? '',
                    'customer_state'    => $PurchaseOrder->state ?? '',
                    'transport_by'      => optional($PurchaseOrder->transport_by)->name ?? '',
                    'transporter_detail'=> optional($PurchaseOrder->transport_detail)->name ?? '',
                    'terms_conditions'  => $PurchaseOrder->term_and_condition ?? '',
                    'shipping_address'  => $PurchaseOrder->shipping_address ?? '',
                    'billing_address'   => $PurchaseOrder->billing_address ?? '',
                    'total_taxable'     => number_format($PurchaseOrder->total_taxable, 2) ?? '',
                    'gst_amount'        => number_format($PurchaseOrder->gst_amount, 2) ?? '',
                    'tcs_amount'        => number_format($PurchaseOrder->tcs_amount, 2) ?? '',
                    'round_off'         => number_format($PurchaseOrder->round_off, 2) ?? '',
                    'grand_total'       => number_format($PurchaseOrder->grand_total, 2) ?? '',
                ];
                 //dd($data);

                $itemsHtml = '';
                if ($PurchaseOrder->purchase_order_items->isNotEmpty()) {
                        $itemsHtml .= '<table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;">
                        <thead style="background-color: #f2f2f2;">
                            <tr>
                                <th>Item</th>
                                <th>Description</th>
                                <th>HSN</th>
                                <th>Unit</th>
                                <th>Qty</th>
                                <th>Price</th>
                                <th>Discount %</th>
                                <th>Rate</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>';

                    foreach($PurchaseOrder->purchase_order_items as $item) {
                        $itemsHtml .= '<tr>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->product_name ?? '-') . '</td>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->product_description ?? '-') . '</td>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->hsn_code ?? '-') . '</td>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->unit ?? '-') . '</td>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->quantity ?? '-') . '</td>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->price_in_amount ?? '-') . '</td>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->discount_percentage ?? '-') . '</td>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->rate_in_amount ?? '-') . '</td>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->amount ?? '-') . '</td>';
                        $itemsHtml .= '</tr>';
                    }
                    $itemsHtml .= '</tbody></table>';
                }


                $data['purchase_order_items_table'] = $itemsHtml;


                $sales_approve_status_id = Helper::getSalesStatusNameById($PurchaseOrder->company_id,"app",'Purchase Order');
                $sales_partially_dispatch_status_id = Helper::getSalesStatusNameById($PurchaseOrder->company_id,"pi",'Purchase Order');
                $sales_fully_dispatch_status_id = Helper::getSalesStatusNameById($PurchaseOrder->company_id,"fi",'Purchase Order');


                //dd($this->oldStatus,$this->newStatus,$this->action);
                //dd($this->action);
                $template = '';
                $api_name = '';
                switch ($this->action) {
                    case 'create':
                        $template = 'purchase_order_create';
                        $api_name = 'PurchaseOrder/store';
                        break;
                    case 'delete':
                        $template = 'purchase_order_deleted';
                        $api_name = 'PurchaseOrder/destroy';
                        break;
                    case 'update':
                        if ($this->oldStatus !=  $this->newStatus && $this->newStatus == $sales_approve_status_id) {
                            $template = 'purchase_order_approve';
                            $api_name = 'PurchaseOrder/change_sales_order_status';
                        }
                        break;
                    default:
                        // $template = 'quotation_update';
                        // $api_name = 'SalesOrder/update';
                        break;
                }

                //dd($template,$api_name);


                // Helper::mail_new(
                //     $quotation->company_id,
                //     'Quotation',
                //     'quotation_create',
                //     optional($PurchaseOrder->vendor)->email_id),
                //     'quotation/store',
                //     $data
                // );
                if(!empty($template) && !empty($api_name)){
                    Helper::mail_new(
                        $PurchaseOrder->company_id,
                        'Quotation',
                        $template,
                        optional($PurchaseOrder->vendor)->email_id,
                        optional($PurchaseOrder->vendor)->id,
                        $api_name,
                        $data
                    );
                }


            } catch (\Throwable $e) {
                Log::error('Error in SendSalesOrderJob@created: ' . $e->getMessage(), [
                    'purchase_order_id' => $PurchaseOrder->id,
                    'stack'        => $e->getTraceAsString(),
                ]);
            }
        }
    }
}
