<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisitorGatepass extends Model
{
    use SoftDeletes;

    protected $table = 'visitor_gatepasses';
    protected $fillable = [
        'company_id',
        'visitor_name',
        'contact_number',
        'email',
        'visitor_type_id',
        'organization_name',
        'visiting_datetime',
        'check_in_time',
        'purpose_of_visit_id',
        'team_person_id',
        'department_id',
        'id_proof_type',
        'id_proof_number',
        'vehicle_number',
        'items_carried_in',
        'photo',
        'signature',
        'gate_location_name',
        'status',
        'platform',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    public function purposeOfVisit()
    {
        return $this->belongsTo(PurposeVisitMaster::class, 'purpose_of_visit_id');
    }
    public function teamPerson()
    {
        return $this->belongsTo(TeamPerson::class, 'team_person_id');
    }
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
    public function visitorType()
{
    return $this->belongsTo(VisitorType::class, 'visitor_type_id', 'id');
}

}
