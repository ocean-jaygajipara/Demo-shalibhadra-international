<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySubscriptionAddons extends Model
{
    protected $table = 'company_subscription_addons';

    protected $fillable = [
        'company_id',
        'plan_id',
        'subscription_plan_id',
        'add_days',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    public $timestamps = true;

    public function plan()
    {
        return $this->belongsTo(PlanMaster::class, 'plan_id', 'id');
    }

    public function company() {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
