<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamAttendance extends Model
{
    use SoftDeletes;

    protected $table = 'team_attendances';

    protected $appends = ['punch_in_image_url', 'punch_out_image_url'];

    static $folderPath = "team_attendances/";
    // protected static $folderPath = "team_attendances/".now()->format('Y')."-".now()->format('m');

    protected $fillable = [
        'company_id', 'team_person_id',

        'punch_in_time', 'punch_in_image_path', 'punch_in_latitude', 'punch_in_longitude', 'punch_in_address',

        'punch_out_time', 'punch_out_image_path', 'punch_out_latitude', 'punch_out_longitude', 'punch_out_address',

        'punch_out_flag', 'working_time', 'punch_in_flag',

        'status', 'verify_by', 'created_by', 'updated_by', 'deleted_by',
    ];
     public static $InOut = [
        'in_out' => "In/Out",
        'in' => "In",
        'out' => "Out",
    ];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($record) {
        });

        static::updating(function ($record) {
        });
    }

    public function getPunchInImageUrlAttribute(){
        # punch_in_image_url
        $image_url = 'https://placehold.co/125x125?text=M';
        if($this->punch_in_image_path){
            if(file_exists(public_path($this->punch_in_image_path))){
                return asset($this->punch_in_image_path);
            }
        }
        return $image_url;
    }
    public function getPunchOutImageUrlAttribute(){
        # punch_out_image_url
        $image_url = 'https://placehold.co/125x125?text=M';
        if($this->punch_out_image_path){
            if(file_exists(public_path($this->punch_out_image_path))){
                return asset($this->punch_out_image_path);
            }
        }
        return $image_url;
    }

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // Relationships
    public function team_person()
    {
        return $this->belongsTo(TeamPerson::class, 'team_person_id');
    }
}
