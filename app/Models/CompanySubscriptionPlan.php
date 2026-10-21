<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySubscriptionPlan extends Model
{
    protected $table = 'company_subscription_plan';

    protected $fillable = [
        'company_id',
        'plan_id',
        'plan_from',
        'plan_to',
        'extra_detail',
        'plan_expiry_date',
        'subscription_status',
        'created_by',
        'updated_by',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'extra_detail' => 'array',
        'plan_from' => 'date',
        'plan_to' => 'date',
        'plan_expiry_date' => 'date',
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
