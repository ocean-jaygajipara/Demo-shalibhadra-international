<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;
    protected $table = 'invoice';
    protected $fillable = [
        'company_id',
        'product_category_id',
        'customer_id',
        'term_condition_id',
        'packing_slip_id',
        'sales_order_id',
        'po_number',
        'po_date',
        'contact_person_name',
        'customer_name',
        'phone',
        'state',
        'gst_no',
        'price_list_id',
        'sales_status_id',
        'address',
        'expected_dispatch_date',
        'shipping_address',
        'shipping_customer_id',
        'shipping_gst_no',
        'billing_address',
        'invoice_order_date',
        'invoice_order_no',
        'invoice_type',
        'transport_by_id',
        'transport_details_id',
        'cash_discount_percentage',
        'cash_discount_amount',
        'additional_discount_percentage',
        'additional_discount_amount',
        'packing_forwarding_charge',
        'transport_charge',
        'freight_charge',
        'total_taxable',
        'is_gst_applicable',
        'gst_amount',
        'is_tcs_applicable',
        'tcs_amount',
        'round_off',
        'grand_total',
        'master_quantity',
        'master_amount',
        'master_taxable_amount',
        'master_gst_amount',
        'master_sub_total',
        'term_and_condition',
        'regards',
        'notes',
        'latitude',
        'longitude',
        'status',
        'platform',

        'generate_eway',
        'eway_bill_id',
        'eway_bill_no',
        'eway_bill_status',
        'transporter_id',
        'transporter_name',
        'transporter_gstin',

        'irn',
        'ack_no',
        'ack_date',
        'signed_invoice',
        'signed_qr_code',
        'irn_status',
        'e_invoice_status',
        'e_invoice_response',
        'irn_generated_at',
        'cancel_date',
        'cancel_reason',
        'cancel_remarks',

        'created_type',
        'updated_type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function sales_status()
    {
        return $this->belongsTo(SalesStatus::class, 'sales_status_id', 'id');
    }

    public function invoice_items()
    {
        return $this->hasMany(InvoiceItems::class);
    }

    public function team_person()
    {
        return $this->belongsTo(TeamPerson::class, 'created_by', 'id');
    }

    public function admin_person()
    {
        return $this->belongsTo(AdminSoftware::class, 'created_by', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function shipping_customer()
    {
        return $this->belongsTo(Customer::class, 'shipping_customer_id', 'id');
    }

    public function term_condition()
    {
        return $this->belongsTo(TermsAndCondition::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function price_list()
    {
        return $this->belongsTo(PriceList::class);
    }

    public function transport_by()
    {
        return $this->belongsTo(MasterTransportBy::class, 'transport_by_id', 'id');
    }

    public function transport_detail()
    {
        return $this->belongsTo(MasterTransportDetail::class, 'transport_details_id', 'id');
    }

    public function packing_slips()
    {
        return $this->belongsTo(PackingSlip::class, 'packing_slip_id', 'id');
    }

    public function sales_order()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'id');
    }

    public function ewayBill()
    {
        return $this->belongsTo(EwayBill::class, 'eway_bill_no', 'eway_bill_no');
    }

    /**
     * Generate invoice number for the invoice
     * Format: FY/PREFIX/001 (e.g., 24-25/INV/001)
     *
     * @return string|null Generated invoice number or null if already exists or conditions not met
     */
    public function generateInvoiceNumber()
    {
        // Generate only if empty
        if (!empty($this->invoice_order_no)) {
            return null;
        }

        // Allow only Approved invoices
        if (!$this->sales_status || $this->sales_status->statusslug !== 'app') {
            return null;
        }

        /** -------------------------------
         *  Financial Year (Apr–Mar)
         *  ------------------------------- */
        $year  = date('Y');
        $month = date('m');

        $short = fn ($y) => substr($y, -2);

        $fy = ($month >= 4)
            ? $short($year) . '-' . $short($year + 1)
            : $short($year - 1) . '-' . $short($year);

        /** -------------------------------
         *  Company Prefix
         *  ------------------------------- */
        $companyPrefix = $this->company?->company_details?->invoice_prefix ?? 'INV';

        /** -------------------------------
         *  Fetch LAST invoice (including deleted)
         *  ------------------------------- */
        $lastInvoice = static::withTrashed()
            ->where('company_id', $this->company_id)
            ->whereNotNull('invoice_order_no')
            ->where('id', '!=', $this->id)
            ->orderByDesc('id')
            ->first();

        /** -------------------------------
         *  Calculate Next Number
         *  ------------------------------- */
        $nextNumber = 1;

        if ($lastInvoice && preg_match('/(\d+)$/', $lastInvoice->invoice_order_no, $m)) {
            $nextNumber = ((int) $m[1]) + 1;
        }

        /** -------------------------------
         *  Generate Invoice No
         *  Format: FY/PREFIX/001
         *  ------------------------------- */
        do {
            $invoiceNo = $fy . '/' . $companyPrefix . '/' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (
            static::withTrashed()
                ->where('company_id', $this->company_id)
                ->where('invoice_order_no', $invoiceNo)
                ->exists()
        );
        return $invoiceNo;
    }
}
