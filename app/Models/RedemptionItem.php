<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RedemptionItem extends Model
{
    use SoftDeletes;

    protected $table = 'redemption_items';

    public $appends = ['scanned_user'];

    protected $fillable = [
        'company_id',
        'redemption_id',
        'redemption_code_label',
        'collect_points',
        'scanned_user_id',
        'scanned_date',
        'customer_id',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function redemption()
    {
        return $this->belongsTo(Redemption::class, 'redemption_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function parent_redeption()
    {
        return $this->belongsTo(Redemption::class, 'redemption_id', 'id');
    }

    public function getScannedUserAttribute()
    {
        # scanned_user
        if ($this->scanned_user_id) {
            return Customer::where('id', $this->scanned_user_id)->first();
        }
        return null;
    }
}
