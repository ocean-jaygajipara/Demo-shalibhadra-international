<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MainMenu extends Model
{
    use SoftDeletes;

    protected $table = 'main_menu';

    protected $fillable = [
        'name',
        'menu_icon',
        'order_by',
        'platform',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function sub_menu()
    {
        return $this->hasMany(SubMenu::class, 'main_menu_id')->orderBy('order_by', 'asc');
    }
}
