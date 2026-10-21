<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackingSlipReminder extends Model
{
    protected $table = 'packing_slip_reminders';

    protected $fillable = [
        'packing_slip_id',
        'user_id',
        'user_type',
        'last_sent_at',
        'is_completed',
    ];

    protected $casts = [
        'last_sent_at' => 'datetime',
        'is_completed' => 'boolean',
    ];

    public function packingSlip()
    {
        return $this->belongsTo(PackingSlip::class, 'packing_slip_id');
    }

    public function teamPerson()
    {
        return $this->belongsTo(TeamPerson::class, 'user_id');
    }
}
