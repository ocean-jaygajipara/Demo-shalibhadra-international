<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarketingStatus extends Model
{
    use HasFactory, SoftDeletes;
    public $table = "marketing_statuses";

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'display_order',
        'status_color',
        'status_color_2',
        'text_color',
        'is_default',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($record) {
            if ($record->is_default == 1) {
                self::where('company_id', $record->company_id)
                    ->where('type', $record->type)
                    ->update(['is_default' => 0]);
            }
            if (empty($record->display_order)) {
                $maxDisplayOrder = self::where('company_id', $record->company_id)
                    ->where('type', $record->type)
                    ->max(\Illuminate\Support\Facades\DB::raw('CAST(display_order AS UNSIGNED)'));
                $record->display_order = $maxDisplayOrder ? ($maxDisplayOrder + 1) : 1;
            }
        });

        /** Call After create */
        static::created(function ($record) {});

        /** Call Before update */
        static::updating(function ($record) {
            if ($record->is_default == 1) {
                self::where('company_id', $record->company_id)
                    ->where('type', $record->type)
                    ->where('id', '!=', $record->id)
                    ->update(['is_default' => 0]);
            }
        });

        /** Call After update */
        static::updated(function ($record) {});
    }

    public static function checkIsSetDefault($company_id)
    {
        if (request()->has('is_default')) {
            MarketingStatus::where('company_id', $company_id)->where('type', request()?->type)->update(['is_default' => 0]);
        }
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function inquiries()
    {
        return $this->hasMany(Inquiry::class, 'inquiry_status_id');
        // ->where('company_id', $this->company_id);
    }
}
