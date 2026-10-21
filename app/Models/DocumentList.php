<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DocumentList extends Model
{
    use HasFactory, SoftDeletes;
    public $table = "document_lists";

    protected $fillable = [
        'company_id',
        'document_type_id',
        'name',
        'status',
        'image',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function document_type()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id', 'id');
    }
}
