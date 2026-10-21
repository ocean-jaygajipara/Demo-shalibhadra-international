<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseSubCategory extends Model
{
    use HasFactory, SoftDeletes;
    public $table = "expense_sub_categories";

    protected $fillable = [
        'company_id',
        'team_person_ids',
        'expense_category_id',
        'name',
        'expense_type',
        'is_image_required',
        // General
        'min_amount',
        'max_amount',
        // Kilometer
        'per_km_rate',
        // Food
        'fix_amount',
        'from_time',
        'to_time',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public static $expense_type = [
        'General' => "General",
        'KM' => "KM",
        'Food' => "Food",
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    public function expense_category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id', 'id');
    }
    public function team_person()
    {
        return $this->belongsTo(TeamPerson::class, 'team_person_ids', 'id');
    }

    public function getTeamPersonsAttribute()
    {
        $ids = explode(',', $this->team_person_ids);
        return TeamPerson::whereIn('id', $ids)->get();
    }

}
