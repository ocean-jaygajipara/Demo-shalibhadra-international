<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpoMarketingSlider extends Model
{
    use SoftDeletes;
    public $table = "expo_marketing_sliders";

    static $folderPath = "expo-marketing-sliders/";

    protected $appends = ['attachment_url'];

    protected $fillable = [
        'company_id',
        'name',
        'attachment',
        'redirect_url',
        'sequence',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];


    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
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
