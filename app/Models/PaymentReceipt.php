<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class PaymentReceipt extends Model
{
    use SoftDeletes;
    public $table = "payment_receipts";
    protected $fillable = [
        'company_id',
        'date',
        'customer_type_id',
        'customer_id',
        'receipt_type',
        'quotation_id',
        'sales_order_id',
        'receipt_no',
        'team_person_ids',
        'payment_by',
        'cheque_no',
        'upi_no',
        'payment_reason',
        'payment_mode',
        'amount',
        'remark',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    public static $receipt_type = [
        "general" => "General",
        "quotation" => "Quotation",
        "order" => "Order",
        "invoice" => "Invoice",

    ];
    public static $payment_by = [
        "cash" => "Cash",
        "cheque" => "Cheque",
        "bank_transfer" => "Bank Transfer",
        "upi" => "UPI",
        "other" => "Other",

    ];
    public static $payment_mode = [
        "credit" => "Credit",
        "debit" => "Debit",
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    public function customerType()
    {
        return $this->belongsTo(CustomerType::class, 'customer_type_id');
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
    public function team_person()
    {
        return $this->belongsTo(TeamPerson::class, 'team_person_ids', 'id');
    }
    // App\Models\PaymentReceipt.php

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'id');
    }

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'quotation_id', 'id');
    }
}
