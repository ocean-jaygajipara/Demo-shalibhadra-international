<?php

namespace App\Jobs;
use App\Models\SalesOrder;
use App\Helpers\Helper;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SendSalesOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $sales_order_id;
    protected $action;
    protected $oldStatus;
    protected $newStatus;
    public function __construct($sales_order_id, $action, $oldStatus = null, $newStatus = null)
    {
        $this->sales_order_id = $sales_order_id;
        $this->action = $action;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $SalesOrder = SalesOrder::with(['sales_order_items','company', 'customer', 'team_person', 'quotation','admin_person','transport_by','transport_detail'])->withTrashed()->findOrFail($this->sales_order_id);
        //dd($SalesOrder);

        if (optional($SalesOrder->company)->id && !empty(optional($SalesOrder->customer)->email)) {

            try {

                $data = [
                    'customer_name'     => $SalesOrder->customer->customer_name ?? '',
                    'sales_order_no'      => $SalesOrder->sales_order_no ?? '',
                    'sales_order_date'    => $SalesOrder->sales_order_date ?? '',
                    'company_name'      => $SalesOrder->company->company_name ?? '',
                    'customer_address'  => $SalesOrder->address ?? '',
                    'customer_phone'    => $SalesOrder->phone ?? '',
                    'customer_state'    => $SalesOrder->state ?? '',
                    'transport_by'      => optional($SalesOrder->transport_by)->name ?? '',
                    'transporter_detail'=> optional($SalesOrder->transport_detail)->name ?? '',
                    'terms_conditions'  => $SalesOrder->term_and_condition ?? '',
                    'shipping_address'  => $SalesOrder->shipping_address ?? '',
                    'billing_address'   => $SalesOrder->billing_address ?? '',
                    'total_taxable'     => number_format($SalesOrder->total_taxable, 2) ?? '',
                    'gst_amount'        => number_format($SalesOrder->gst_amount, 2) ?? '',
                    'tcs_amount'        => number_format($SalesOrder->tcs_amount, 2) ?? '',
                    'round_off'         => number_format($SalesOrder->round_off, 2) ?? '',
                    'grand_total'       => number_format($SalesOrder->grand_total, 2) ?? '',
                ];
                // dd($data);

                $itemsHtml = '';
                if ($SalesOrder->sales_order_items->isNotEmpty()) {
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

                    foreach($SalesOrder->sales_order_items as $item) {
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


                $data['sales_order_items_table'] = $itemsHtml;


                $sales_approve_status_id = Helper::getSalesStatusNameById($SalesOrder->company_id,"app",'Sales Orders');
                $sales_partially_dispatch_status_id = Helper::getSalesStatusNameById($SalesOrder->company_id,"pd",'Sales Orders');
                $sales_fully_dispatch_status_id = Helper::getSalesStatusNameById($SalesOrder->company_id,"fd",'Sales Orders');

                //dd($this->oldStatus,$this->newStatus);
                //dd($this->action);
                $template = '';
                $api_name = '';
                switch ($this->action) {
                    case 'create':
                        $template = 'sales_order_create';
                        $api_name = 'SalesOrder/store';
                        break;
                    case 'delete':
                        $template = 'sales_order_deleted';
                        $api_name = 'SalesOrder/destroy';
                        break;
                    case 'update':
                        if ($this->oldStatus !=  $this->newStatus && $this->newStatus == $sales_approve_status_id) {
                            $template = 'sales_order_approve';
                            $api_name = 'sales-order/change_sales_order_status';
                        }
                        break;
                    default:
                        // $template = 'quotation_update';
                        // $api_name = 'SalesOrder/update';
                        break;
                }

                //dd($template,$api_name);

                //dd($data);
                // Helper::mail_new(
                //     $quotation->company_id,
                //     'Quotation',
                //     'quotation_create',
                //     $customerDetail->email,
                //     'quotation/store',
                //     $data
                // );
                if(!empty($template) && !empty($api_name)){
                    Helper::mail_new(
                        $SalesOrder->company_id,
                        'Quotation',
                        $template,
                        optional($SalesOrder->customer)->email,
                        optional($SalesOrder->customer)->id,
                        $api_name,
                        $data
                    );
                }


            } catch (\Throwable $e) {
                Log::error('Error in SendSalesOrderJob@created: ' . $e->getMessage(), [
                    'sales_order_id' => $SalesOrder->id,
                    'stack'        => $e->getTraceAsString(),
                ]);
            }
        }
    }
}
