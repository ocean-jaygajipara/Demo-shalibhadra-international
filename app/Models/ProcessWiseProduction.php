<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProcessWiseProduction extends Model
{
    use HasFactory;
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $guarded = ['id'];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function used_materials()
    {
        return $this->hasMany(ProcessWiseProductionUsedMaterial::class, 'process_wise_production_id');
    }

    public function by_products()
    {
        return $this->hasMany(ProcessWiseProductionByProduct::class, 'process_wise_production_id');
    }
}
