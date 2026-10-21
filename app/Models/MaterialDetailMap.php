<?php

namespace App\Models;

use App\Helpers\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialDetailMap extends Model
{
    use SoftDeletes;
    public $table = "material_detail_map";

    protected $fillable = [
        'company_id',
        'material_id',
        'product_id',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function material()
    {
        return $this->belongsTo(MaterialDetail::class, 'material_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
