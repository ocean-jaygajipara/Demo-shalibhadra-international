<?php

namespace App\Jobs;
use App\Models\Inward;
use App\Helpers\Helper;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SendInwardOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $inward_id;
    protected $action;
    protected $oldStatus;
    protected $newStatus;
    public function __construct($inward_id, $action, $oldStatus = null, $newStatus = null)
    {
        $this->inward_id = $inward_id;
        $this->action = $action;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $Inward = Inward::with(['vendor', 'purchase_order', 'inward_items', 'warehouse','company', 'team_person','admin_person','transport_by','transport_detail'])->withTrashed()->findOrFail($this->inward_id);
        //dd($Dispatch);

        if (optional($Inward->company)->id && !empty(optional($Inward->vendor)->email_id)) {

            try {

                $data = [
                    'customer_name'     => $Inward->customer->customer_name ?? '',
                    'inward_no'      => $Inward->inward_no ?? '',
                    'inward_date'    => $Inward->inward_date ?? '',
                    'purchase_order_no'      => $Inward->purchase_order->purchase_order_no ?? '',
                    'purchase_order_date'    => $Inward->purchase_order->purchase_order_date ?? '',
                    'company_name'      => $Inward->company->company_name ?? '',
                    'customer_address'  => $Inward->address ?? '',
                    'customer_phone'    => $Inward->phone ?? '',
                    'customer_state'    => $Inward->state ?? '',
                    'transport_by'      => optional($Inward->transport_by)->name ?? '',
                    'transporter_detail'=> optional($Inward->transport_detail)->name ?? '',
                    'terms_conditions'  => $Inward->term_and_condition ?? '',
                    'shipping_address'  => $Inward->shipping_address ?? '',
                    'billing_address'   => $Inward->billing_address ?? '',
                    'total_taxable'     => number_format($Inward->total_taxable, 2) ?? '',
                    'gst_amount'        => number_format($Inward->gst_amount, 2) ?? '',
                    'tcs_amount'        => number_format($Inward->tcs_amount, 2) ?? '',
                    'round_off'         => number_format($Inward->round_off, 2) ?? '',
                    'grand_total'       => number_format($Inward->grand_total, 2) ?? '',
                ];
                // dd($data);
                //dd($Inward->dispatch_items);

                $itemsHtml = '';
                if ($Inward->inward_items->isNotEmpty()) {
                        $itemsHtml .= '<table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;">
                        <thead style="background-color: #f2f2f2;">
                            <tr>
                                <th>Item</th>
                                <th>Description</th>
                                <th>Unit</th>
                                <th>Order Remaining Qty</th>
                                <th>Available Qty</th>
                                <th>Inward Qty</th>
                            </tr>
                        </thead>
                        <tbody>';

                    foreach($Inward->inward_items as $item) {
                        $itemsHtml .= '<tr>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->product_name ?? '-') . '</td>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->product_description ?? '-') . '</td>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->unit ?? '-') . '</td>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->remaining_quantity ?? '-') . '</td>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->available_quantity ?? '-') . '</td>';
                        $itemsHtml .= '<td>' . htmlspecialchars($item->inward_quantity ?? '-') . '</td>';
                        $itemsHtml .= '</tr>';
                    }
                    $itemsHtml .= '</tbody></table>';
                }

                $data['inward_items_table'] = $itemsHtml;


                $sales_partially_dispatch_status_id = Helper::getSalesStatusNameById($Inward->company_id,"pi",'Purchase Order');
                $sales_fully_dispatch_status_id = Helper::getSalesStatusNameById($Inward->company_id,"fi",'Purchase Order');

                // dd($this->oldStatus,$this->newStatus);
                // dd($this->action);
                $template = '';
                $api_name = '';
                switch ($this->action) {
                    case 'create':
                        if ($this->newStatus == $sales_partially_dispatch_status_id) {
                            $template = 'purchase_order_partially_inward';
                            $api_name = 'inward/store';
                        } elseif ($this->newStatus == $sales_fully_dispatch_status_id) {
                            $template = 'purchase_order_fully_inward';
                            $api_name = 'inward/store';
                        }
                        break;
                    case 'delete':
                        $template = 'inward_deleted';
                        $api_name = 'inward/destroy';
                        break;
                    default:
                        // $template = 'quotation_update';
                        // $api_name = 'SalesOrder/update';
                        break;
                }

                //dd($template,$api_name);

                if(!empty($template) && !empty($api_name)){
                    Helper::mail_new(
                        $Inward->company_id,
                        'Quotation',
                        $template,
                        optional($Inward->vendor)->email_id,
                        optional($Inward->vendor)->id,
                        $api_name,
                        $data
                    );
                }


            } catch (\Throwable $e) {
                Log::error('Error in SendInwardOrderJob@created: ' . $e->getMessage(), [
                    'dispatch_id' => $Inward->id,
                    'stack'        => $e->getTraceAsString(),
                ]);
            }
        }
    }
}
