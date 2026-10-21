<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visit extends Model
{
    use SoftDeletes;

    protected $table = 'visits';

    static $folderPath = "visit/";

    protected $appends = ['start_image_url', 'stop_image_url', 'total_time_duration' ];

    protected $fillable = [
        'company_id',
        'team_person_id',

        'model',
        'model_id',

        'meet_person_name',
        'visit_start_date',
        'visit_start_remark',
        'visit_start_latitude',
        'visit_start_longitude',
        'visit_start_address',
        'start_image',

        'visit_stop_remark',
        'visit_stop_date',
        'visit_stop_latitude',
        'visit_stop_longitude',
        'visit_stop_address',
        'stop_image',

        'status',
        'platform',
        'extra_detail',

        'created_by',
        'updated_by',
        'deleted_by',
    ];


    protected $dates = ['visit_start_date', 'deleted_at'];

    public function getTotalTimeDurationAttribute(){
        # total_time_duration
        if($this->visit_start_date && $this->visit_stop_date){
            return Helper::getTimeDifference($this->visit_start_date, $this->visit_stop_date);
        }
        return "-";
    }

    // Example relationships (customize as per your app)
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function teamperson()
    {
        return $this->belongsTo(TeamPerson::class, 'team_person_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function getStartImageUrlAttribute()
    {
        # start_image_url
        $image_url = 'https://placehold.co/125x125?text=Visit';
        if ($this->start_image) {
            if (file_exists(public_path($this->start_image))) {
                return asset($this->start_image);
            }
        }
        return $image_url;
    }

    public function getStopImageUrlAttribute()
    {
        # stop_image_url
        $image_url = 'https://placehold.co/125x125?text=Visit';
        if ($this->stop_image) {
            if (file_exists(public_path($this->stop_image))) {
                return asset($this->stop_image);
            }
        }
        return $image_url;
    }
}
