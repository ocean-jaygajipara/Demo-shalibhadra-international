<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InquiryImportFile extends Model
{
    protected $table = 'inquiry_import_file';
    protected $fillable = [
        'company_id',
        'filename',
        'total_inquiries',
        'total_success_inquiries',
        'total_failed_inquiries',
        'errors',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
