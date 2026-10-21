<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderReminder extends Model
{
    protected $table = 'sales_order_reminders';

    protected $fillable = [
        'sales_order_id',
        'user_id',
        'user_type',
        'last_sent_at',
        'is_completed',
    ];

    protected $casts = [
        'last_sent_at' => 'datetime',
        'is_completed' => 'boolean',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function teamPerson()
    {
        return $this->belongsTo(TeamPerson::class, 'user_id');
    }
}
