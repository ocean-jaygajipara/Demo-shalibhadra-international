<?php

namespace App\Jobs;
use App\Models\Dispatch;
use App\Helpers\Helper;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SendDispatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $dispatch_id;
    protected $action;
    protected $oldStatus;
    protected $newStatus;
    public function __construct($dispatch_id, $action, $oldStatus = null, $newStatus = null)
    {
        $this->dispatch_id = $dispatch_id;
        $this->action = $action;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $Dispatch = Dispatch::with(['sales_order','company', 'customer', 'team_person', 'dispatch_items','admin_person','transport_by','transport_detail'])->withTrashed()->findOrFail($this->dispatch_id);
        //dd($Dispatch);

        if (optional($Dispatch->company)->id && !empty(optional($Dispatch->customer)->email)) {

            try {

                $data = [
                    'customer_name'     => $Dispatch->customer->customer_name ?? '',
                    'dispatch_no'      => $Dispatch->dispatch_no ?? '',
                    'dispatch_date'      => $Dispatch->dispatch_date ?? '',
                    'sales_order_no'      => $Dispatch->sales_order->sales_order_no ?? '',
                    'sales_order_date'    => $Dispatch->sales_order->sales_order_date ?? '',
                    'company_name'      => $Dispatch->company->company_name ?? '',
                    'customer_address'  => $Dispatch->address ?? '',
                    'customer_phone'    => $Dispatch->phone ?? '',
                    'customer_state'    => $Dispatch->state ?? '',
                    'transport_by'      => optional($Dispatch->transport_by)->name ?? '',
                    'transporter_detail'=> optional($Dispatch->transport_detail)->name ?? '',
                    'terms_conditions'  => $Dispatch->term_and_condition ?? '',
                    'shipping_address'  => $Dispatch->shipping_address ?? '',
                    'billing_address'   => $Dispatch->billing_address ?? '',
                    'total_taxable'     => number_format($Dispatch->total_taxable, 2) ?? '',
                    'gst_amount'        => number_format($Dispatch->gst_amount, 2) ?? '',
                    'tcs_amount'        => number_format($Dispatch->tcs_amount, 2) ?? '',
                    'round_off'         => number_format($Dispatch->round_off, 2) ?? '',
                    'grand_total'       => number_format($Dispatch->grand_total, 2) ?? '',
                ];
                // dd($data);
                //dd($Dispatch->dispatch_items);

                $itemsHtml = '';
                if ($Dispatch->dispatch_items->isNotEmpty()) {
                        $itemsHtml .= '<table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;">
                        <thead style="background-color: #f2f2f2;">
                            <tr>
                                <th>Item</th>
                                <th>Description</th>
                                <th>Unit</th>
                                <th>Order Remaining Qty</th>
                                <th>Available Qty</th>
                                <th>Dispatch Qty</th>
                            </tr>
                        </thead>
                        <tbody>';

                    foreach($Dispatch->dispatch_items as $item) {
                        $itemsHtml .= '<tr>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->product_name ?? '-') . '</td>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->product_description ?? '-') . '</td>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->unit ?? '-') . '</td>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->remaining_quantity ?? '-') . '</td>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->available_quantity ?? '-') . '</td>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->dispatch_quantity ?? '-') . '</td>';
                        $itemsHtml .= '</tr>';
                    }
                    $itemsHtml .= '</tbody></table>';
                }

                $data['dispatch_items_table'] = $itemsHtml;


                $sales_partially_dispatch_status_id = Helper::getSalesStatusNameById($Dispatch->company_id,"pd",'Sales Orders');
                $sales_fully_dispatch_status_id = Helper::getSalesStatusNameById($Dispatch->company_id,"fd",'Sales Orders');

                // dd($this->oldStatus,$this->newStatus);
                // dd($this->action);
                $template = '';
                $api_name = '';
                switch ($this->action) {
                    case 'create':
                        if ($this->newStatus == $sales_partially_dispatch_status_id) {
                            $template = 'sales_order_partially_dispatch';
                            $api_name = 'dispatch/store';
                        } elseif ($this->newStatus == $sales_fully_dispatch_status_id) {
                            $template = 'sales_order_fully_dispatch';
                            $api_name = 'dispatch/store';
                        }
                        break;
                    case 'delete':
                        $template = 'dispatch_deleted';
                        $api_name = 'dispatch/destroy';
                        break;
                    default:
                        // $template = 'quotation_update';
                        // $api_name = 'SalesOrder/update';
                        break;
                }

                //dd($template,$api_name);


                if(!empty($template) && !empty($api_name)){
                    Helper::mail_new(
                        $Dispatch->company_id,
                        'Quotation',
                        $template,
                        optional($Dispatch->customer)->email,
                        optional($Dispatch->customer)->id,
                        $api_name,
                        $data
                    );
                }


            } catch (\Throwable $e) {
                Log::error('Error in SendDispatchJob@created: ' . $e->getMessage(), [
                    'dispatch_id' => $Dispatch->id,
                    'stack'        => $e->getTraceAsString(),
                ]);
            }
        }
    }
}
