<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImportFile extends Model
{
    protected $table = 'product_import_file';
    protected $fillable = [
        'company_id',
        'filename',
        'total_products',
        'total_success_products',
        'total_failed_products',
        'errors',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
