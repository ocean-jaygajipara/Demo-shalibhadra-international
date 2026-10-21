<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesStatus extends Model
{
    use HasFactory, SoftDeletes;

    public $table = "sales_statuses";

    protected $fillable = [
        'company_id',
        'salestype',
        'statusname',
        'statusslug',
        'status_color',
        'display_order',
        'status',
        'is_redirect',
        'parent_status_id',
        'is_revise',
        'is_repeat',
        'created_by',
        'updated_by',
        'deleted_by'
    ];
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
