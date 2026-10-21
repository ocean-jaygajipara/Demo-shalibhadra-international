<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    public $table = "expenses";

    static $folderPath = "expenses/";

    protected $fillable = [
        'company_id',
        'team_person_id',
        'expense_category_id',
        'expense_subcategory_id',
        'date',
        'req_amount',
        'pass_amount',
        'reason',
        'remark',
        'attachment',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $appends = ['attachment_url'];

    public function getAttachmentUrlAttribute()
    {
        # attachment_url
        if ($this->attachment) {
            if (file_exists(public_path($this->attachment))) {
                return asset($this->attachment);
            }
        }
        return null;
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    public function expense_category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id', 'id');
    }
    // public function expense_subcategory()
    // {
    //     return $this->belongsTo(ExpenseSubCategory::class, 'expense_subcategory_id', 'id');
    // }
    public function expense_sub_category()
    {
        return $this->belongsTo(ExpenseSubCategory::class, 'expense_subcategory_id', 'id');
    }


    public function team_person()
    {
        return $this->belongsTo(TeamPerson::class, 'team_person_id', 'id');
    }
       public function getTeamPersonsAttribute()
    {
        $ids = explode(',', $this->team_person_ids);
        return TeamPerson::whereIn('id', $ids)->get();
    }
}
