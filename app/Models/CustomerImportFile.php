<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerImportFile extends Model
{
    protected $table = 'customer_import_file';
    protected $fillable = [
        'company_id',
        'filename',
        'total_customers',
        'total_success_customers',
        'total_failed_customers',
        'errors',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
