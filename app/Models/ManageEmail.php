<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ManageEmail extends Model
{
    use SoftDeletes;
    public $table = "manage_email";
    protected $fillable = [
        'company_id',
        'module',
        'ntype',
        'name',
        'subject',
        'body',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    public static $ManageForEmail = [
        'registrion_company' => "Registrsion Company",
        'place_order' => "Place Order",
        'order_dispatch' => "Order Dispatch",
        'Inquiry' => "Inquiry",
    ];
    public static $ManageForSuggetion = [
            '[company_name]',
            '[customer_name]',
            '[mobile_number]',
            '[whatsapp_number]',
            '[client_code]',

    ];

}
