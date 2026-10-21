<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessWiseProductionUsedMaterial extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    
    public function material()
    {
        return $this->belongsTo(Product::class, 'material_id');
    }
}
