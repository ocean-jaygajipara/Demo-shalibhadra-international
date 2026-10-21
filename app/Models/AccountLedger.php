<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountLedger extends Model
{
    protected $fillable = [
        'company_id',
        'customer_type_id',
        'customer_id',
        'entry_date',
        'receipt_id',
        'reference_type',
        'quotation_id',
        'sales_order_id',
        'payment_mode',
        'amount',
        'debit_amount',
        'credit_amount',
        'description',
        'closing_balance',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }
    public function customerType()
    {
        return $this->belongsTo(CustomerType::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
