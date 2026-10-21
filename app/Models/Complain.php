<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Complain extends Model
{
    use SoftDeletes;
    public $table = "complains";

    protected $appends = ['image_url'];
    protected $fillable = [
        'company_id',
        'source_complain_id',
        'complain_type_id',
        'customer_type_id',
        'customer_id',
        'complain_no',
        'remark',
        'complain_created_by_id',
        'complain_assign_to_id',
        'status',
        'image',
        'created_by',
        'updated_by',
        'deleted_by'

    ];
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function teamperson()
    {
        return $this->belongsTo(TeamPerson::class);
    }

    public function complaincreatedbyteamperson()
    {
        return $this->belongsTo(TeamPerson::class, 'complain_created_by_id', 'id');
    }

    public function complainassigntoteamperson()
    {
        return $this->belongsTo(TeamPerson::class, 'complain_assign_to_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function sourcecomplain()
    {
        return $this->belongsTo(SourceOfInquiry::class, 'source_complain_id', 'id');
    }
    public function complaintype()
    {
        return $this->belongsTo(ComplanType::class, 'complain_type_id', 'id');
    }
    public function customertype()
    {
        return $this->belongsTo(CustomerType::class, 'customer_type_id', 'id');
    }
        public function getimageUrlAttribute(){
        # attachment_url
        if($this->image){
            if(file_exists(public_path($this->image))){
                return asset($this->image);
            }
            return public_path($this->image);
        }
        return null;
    }
}
