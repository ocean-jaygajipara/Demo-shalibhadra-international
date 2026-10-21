<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubMenu extends Model
{
    use SoftDeletes;

    protected $table = 'sub_menu';

    protected $fillable = [
        'main_menu_id',
        'name',
        'route_name',
        'url',
        'order_by',
        'platform',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function mainMenu()
    {
        return $this->belongsTo(MainMenu::class, 'main_menu_id', 'id');
    }
}
