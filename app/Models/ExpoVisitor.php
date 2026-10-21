<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpoVisitor extends Model
{
    use SoftDeletes;

    public $table = "expo_visitors";

    protected $fillable = [
        'company_id',
        'expo_id',
        'company_name',
        'person_name',
        'mobile_no',
        'email',
        'website',
        'requirement',
        'inquiry_id',
        'platform',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function expo()
    {
        return $this->belongsTo(ExpoManagement::class, 'expo_id', 'id');
    }
    public function expo_category()
    {
        return $this->belongsTo(ExpoCategory::class, 'expo_category_id');
    }

    public function team_person()
    {
        return $this->belongsTo(TeamPerson::class, 'created_by', 'id');
    }

    public function expo_inquiry(){
        return $this->belongsTo(Inquiry::class,'inquiry_id', 'id');
    }
}
