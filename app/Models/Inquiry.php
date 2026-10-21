<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Helpers\Helper;

class Inquiry extends Model
{
    use SoftDeletes;
    public $table = "inquiries";

    static $folderPath = "inquiry/";

    protected $fillable = [
        'company_id',
        'inquiry_no',
        'customer_name',
        'contact_person_name',
        'mobile_no',
        'whatsapp_no',
        'whatsapp_general_sent_count',
        'last_whatsapp_general_sent_at',
        'last_whatsapp_general_sent_by',
        'email',
        'pincode',
        'website',
        'inquiry_status_id',
        'followup_status_id',
        'source_of_inquiry_id',
        'requirement_details',
        'address',
        'inquiry_date',
        'country_id',
        'state_id',
        'city_id',
        'area_id',
        'pincode',
        'inquiry_created_by',
        'inquiry_assigned_to',
        'attachment',
        'followup_date',
        'followup_details',
        'platform',
        'status',
        'created_type',
        'assigned_to',
        'json_data',
        'platform_unique_id',
        'platform_json',
        'remarks',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function boot()
    {
        parent::boot();

        // Trigger on Create
        static::created(function ($inquiry) {
            $assignedUserIds = explode(',', $inquiry->inquiry_assigned_to ?? '');

            foreach ($assignedUserIds as $assignedUserId) {
                if ($assignedUserId) {

                    $createdByTeamName = $inquiry->created_by_team_name();
                    Helper::sendPushNotification([
                        'company_id'       => $inquiry->company_id,
                        'user_id'       => $assignedUserId,
                        'user_type'     => 'Team',
                        'title'         => "New Inquiry Assigned",
                        'body'          => "You have been assigned a new inquiry (Inquiry No: {$inquiry->inquiry_no}) by {$createdByTeamName}",
                        'module_name'   => 'Inquiry',
                        'module_id'     => $inquiry->id,
                        'module_action' => 'add',
                        'created_by'    => $inquiry->created_by,
                        'created_type'  => $inquiry->created_type,
                        'status'        => 'success',
                    ]);
                }
            }
        });


        static::updating(function ($inquiry) {
            // Only proceed if inquiry_assigned_to has changed
            if ($inquiry->isDirty('inquiry_assigned_to')) {
                $originalAssigned = explode(',', $inquiry->getOriginal('inquiry_assigned_to') ?? '');
                $updatedAssigned  = explode(',', $inquiry->inquiry_assigned_to ?? '');

                // Clean arrays
                $originalAssigned = array_filter(array_map('trim', $originalAssigned));
                $updatedAssigned  = array_filter(array_map('trim', $updatedAssigned));

                // Find newly added assignees
                $newAssigned = array_diff($updatedAssigned, $originalAssigned);

                foreach ($newAssigned as $assignedUserId) {

                    $createdByTeamName = $inquiry->created_by_team_name();

                    Helper::sendPushNotification([
                        'company_id'       => $inquiry->company_id,
                        'user_id'       => $assignedUserId,
                        'user_type'     => 'Team',
                        'title'         => "New Inquiry Assigned",
                        'body'          => "You have been assigned a new inquiry (Inquiry No: {$inquiry->inquiry_no}) by {$createdByTeamName}",
                        'module_name'   => 'Inquiry',
                        'module_id'     =>  $inquiry->id,
                        'module_action' => 'edit',
                        'created_by'    => $inquiry->created_by,
                        'created_type'  => $inquiry->created_type,
                        'status'        => 'success',
                    ]);
                }
            }
        });
    }

    public function getAssignedToNameAttribute()
    {
        if (!$this->inquiry_assigned_to) return [];

        $ids = explode(',', $this->inquiry_assigned_to);

        return TeamPerson::whereIn('id', $ids)->pluck('name')->toArray();
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function inquiry_status()
    {
        return $this->belongsTo(MarketingStatus::class, 'inquiry_status_id', 'id')->withDefault([
            'name' => '-'
        ]);
    }

    // public function followup_status() {
    //     return $this->belongsTo(MarketingStatus::class, 'followup_status_id', 'id');
    // }

    public function followup_status()
    {
        return $this->belongsTo(MarketingStatus::class, 'followup_status_id', 'id')->withDefault([
            'name' => '-'
        ]);
    }

    public function source_of_inquiry()
    {
        return $this->belongsTo(SourceOfInquiry::class, 'source_of_inquiry_id', 'id')->withDefault([
            'name' => '-'
        ]);
    }

    public function getCreatedByNameAttribute()
    {

        // if ($this->created_type === 'Team') {
        //     return optional(TeamPerson::find($this->inquiry_created_by))->name ?? '-';
        // } elseif ($this->created_type === 'Admin') {
        //     return optional(AdminSoftware::find($this->inquiry_created_by))->name ?? '-';
        // }
        return optional(TeamPerson::find($this->inquiry_created_by))->name ?? '-';

        //return '-';
    }

    public function created_by_name()
    {
        if ($this->created_type == "Team") {
            return $this->belongsTo(TeamPerson::class, 'inquiry_created_by', 'id')->withDefault(['name' => '-']);
        } else {
            return $this->belongsTo(AdminSoftware::class, 'inquiry_created_by', 'id')->withDefault(['name' => '-']);
        }
    }


    public function created_by_team_name()
    {
        if ($this->created_type == "Team") {
            return optional(TeamPerson::find($this->created_by))->name ?? '-';
        } else {
            return optional(AdminSoftware::find($this->created_by))->name ?? '-';
        }
    }

    public function assigned_to_name()
    {
        return $this->belongsTo(TeamPerson::class, 'inquiry_assigned_to', 'id')->withDefault([
            'name' => '-'
        ]);
    }


    public function country()
    {
        return $this->belongsTo(MasterCountry::class, 'country_id', 'id');
    }

    public function state()
    {
        return $this->belongsTo(MasterState::class, 'state_id', 'id');
    }

    public function city()
    {
        return $this->belongsTo(MasterCity::class, 'city_id', 'id');
    }

    public function area()
    {
        return $this->belongsTo(MasterArea::class, 'area_id', 'id')->where('company_id', $this->company_id)->where('country_id', $this->country_id)->where('state_id', $this->state_id);
    }

    public function getInquiryToCustomerIdAttribute()
    {
        # inquiry_to_customer_id
        # inqiry_to_customer_id
        if ($this->company_id && $this->mobile_no) {
            $customerToInquiryConverted = Customer::where(['company_id' => $this->company_id, 'mobile_number' => $this->mobile_no, 'country_id' => $this->country_id])->withTrashed()->first();
            return $customerToInquiryConverted?->id . "" ?? null;
        }
        return null;
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
    public function createdBy()
    {
        return $this->belongsTo(TeamPerson::class, 'inquiry_created_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(TeamPerson::class, 'inquiry_assigned_to');
    }
    public function getAssignedToNamesAttribute()
    {
        if (!$this->inquiry_assigned_to) {
            return [];
        }

        $ids = explode(',', $this->inquiry_assigned_to);

        return TeamPerson::whereIn('id', $ids)->pluck('name')->toArray();
    }

    public function inquiry_products()
    {
        return $this->hasMany(InquiryProduct::class, 'inquiry_id', 'id');
    }
}

