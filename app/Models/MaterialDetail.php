<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialDetail extends Model
{
    use SoftDeletes;
    public $table = "material_detail";

    protected $fillable = [
        'company_id',
        'qc_inspection_id',
        'dimension',
        'size',
        'ltl',
        'utl',
        'instrument',
        'size1',
        'size2',
        'size3',
        'size4',
        'size5',
        'size6',
        'size7',
        'size8',
        'size9',
        'size10',
        'remarks',
        'status'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
