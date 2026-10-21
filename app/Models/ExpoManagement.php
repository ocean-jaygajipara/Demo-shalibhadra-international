<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpoManagement extends Model
{
    use HasFactory, SoftDeletes;
    public $table = "expo_management";

    static $folderPath = "expo-management/";

    protected $fillable = [
        'company_id',
        'expo_category_id',
        'name',
        'attachment',
        'start_date',
        'end_date',
        'expo_url',
        'location',
        'platform',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function expo_category()
    {
        return $this->belongsTo(ExpoCategory::class, 'expo_category_id');
    }

    public function has_visitors(){
        return $this->hasMany(ExpoVisitor::class, 'expo_id', 'id');
    }

    public function getAttachmentUrlAttribute()
    {
        # attachment_url
        if ($this->attachment) {
            if (file_exists(public_path($this->attachment))) {
                return asset($this->attachment);
            }
            return public_path($this->attachment);
        }
        return null;
    }


}
