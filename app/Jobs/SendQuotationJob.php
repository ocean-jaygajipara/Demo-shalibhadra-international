<?php

namespace App\Jobs;
use App\Models\Quotation;
use App\Helpers\Helper;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class SendQuotationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $quotationId;
    protected $action;
    protected $oldStatus;
    protected $newStatus;
    public function __construct($quotationId, $action, $oldStatus = null, $newStatus = null)
    {
        $this->quotationId = $quotationId;
        $this->action = $action;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $quotation = Quotation::with(['quotation_items', 'company', 'customer', 'team_person', 'admin_person'])->withTrashed()->findOrFail($this->quotationId);

        if (optional($quotation->company)->id && !empty(optional($quotation->customer)->email)) {

            try {

                $data = [
                    'customer_name'     => $quotation->customer->customer_name ?? '',
                    'quotation_no'      => $quotation->quotation_no ?? '',
                    'quotation_date'    => $quotation->quotation_date ?? '',
                    'company_name'      => $quotation->company->company_name ?? '',
                    'customer_address'  => $quotation->address ?? '',
                    'customer_phone'    => $quotation->phone ?? '',
                    'customer_state'    => $quotation->state ?? '',
                    'transport_by'      => optional($quotation->transport_by)->name ?? '',
                    'transporter_detail'=> optional($quotation->transport_detail)->name ?? '',
                    'terms_conditions'  => $quotation->term_and_condition ?? '',
                    'shipping_address'  => $quotation->shipping_address ?? '',
                    'billing_address'   => $quotation->billing_address ?? '',
                    'total_taxable'     => number_format($quotation->total_taxable, 2) ?? '',
                    'gst_amount'        => number_format($quotation->gst_amount, 2) ?? '',
                    'tcs_amount'        => number_format($quotation->tcs_amount, 2) ?? '',
                    'round_off'         => number_format($quotation->round_off, 2) ?? '',
                    'grand_total'       => number_format($quotation->grand_total, 2) ?? '',
                ];



                $itemsHtml = '<table border="1" cellpadding="6" cellspacing="0" width="100%" style="border-collapse: collapse; font-family: Arial, sans-serif; font-size: 14px;">
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

                foreach($quotation->quotation_items as $item) {
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

                $data['quotation_items_table'] = $itemsHtml;

                //dd($data);
                $sales_approve_status_id = Helper::getSalesStatusNameById($quotation->company_id,"app",'Quotation');
                $sales_order_generated_status_id = Helper::getSalesStatusNameById($quotation->company_id,"og",'Quotation');

                //dd($this->oldStatus,$this->newStatus);
                //dd($this->action);
                $template = '';
                $api_name = '';
                switch ($this->action) {
                    case 'create':
                        $template = 'quotation_create';
                        $api_name = 'quotation/store';
                        break;
                    case 'delete':
                        $template = 'quotation_deleted';
                        $api_name = 'quotation/destroy';
                        break;
                    case 'update':
                        if ($this->oldStatus !=  $this->newStatus && $this->newStatus == $sales_approve_status_id) {
                            $template = 'quotation_approve';
                            $api_name = 'quotation/change_quotation_status';
                        } elseif ($this->oldStatus !=  $this->newStatus && $this->newStatus == $sales_order_generated_status_id) {
                            $template = 'quotation_order_generated';
                            $api_name = 'sales-order/update';
                        }
                        break;
                    default:
                        $template = 'quotation_update';
                        $api_name = 'quotation/update';
                        break;
                }

                //dd($data);

                if(!empty($template) && !empty($api_name)){
                    Helper::mail_new(
                        $quotation->company_id,
                        'Quotation',
                        $template,
                        optional($quotation->customer)->email,
                        optional($quotation->customer)->id,
                        $api_name,
                        $data
                    );
                }


            } catch (\Throwable $e) {
                Log::error('Error in SendQuotationJob@created: ' . $e->getMessage(), [
                    'quotation_id' => $quotation->id,
                    'stack'        => $e->getTraceAsString(),
                ]);
            }
        }
    }
}
