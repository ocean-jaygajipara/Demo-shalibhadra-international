<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Followup extends Model {
    use SoftDeletes;
    public $table = "followups";

    protected $fillable = [
        'company_id',
        'inquiry_id',
        'quotation_id',
        'customer_id',
        'assign_to_id',
        'date_time',
        'reason_id',
        'details',
        'through',
        'status_id',
        'response_details',
        'response_date_time',
        'followup_action',
        'platform',
        'notified_at_15',
        'notified_at_10',
        'notified_at_5',
        'created_type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public static $followFilterList = [
        'all_follow_up' => 'All Follow Up',
        'pending_all_follow_up' => 'Pending All Follow Up',
        'today_all_follow_up' => 'Today All Follow Up',
        'today_pending_all_follow_up' => 'Today Pending Follow Up',
    ];

    protected $appends = ['followup_type' ];

    public function company() {
        return $this->belongsTo(Company::class);
    }

    public function inquiry()
    {
        return $this->belongsTo(Inquiry::class, 'inquiry_id');  // Assuming 'inquiry_id' is the foreign key in 'followups' table
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');  // Assuming 'customer_id' is the foreign key in 'followups' table
    }

    public function getFollowupTypeAttribute(){
        # followup_type
        if($this->inquiry_id){
            return "inquiries";
        } else if($this->quotation_id){
            return "quotation";
        } else if($this->customer_id){
            return "customers";
        }
        return null;
    }


    public function teamPerson()
    {
        return $this->belongsTo(TeamPerson::class, 'created_by');  // Assuming 'created_by' stores the user/team person
    }

    public function marketingStatus()
    {
        return $this->belongsTo(MarketingStatus::class, 'status_id');  // Assuming 'status_id' on 'followups' links to marketing statuses
    }

    public function created_by_name()
    {
        if ($this->created_type == "Team") {
            return $this->belongsTo(TeamPerson::class, 'inquiry_created_by', 'id')->withDefault(['name' => '-']);
        } else {
            return $this->belongsTo(AdminSoftware::class, 'inquiry_created_by', 'id')->withDefault(['name' => '-']);
        }
    }
}
