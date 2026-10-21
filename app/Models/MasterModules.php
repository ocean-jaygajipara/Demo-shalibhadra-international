<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterModules extends Model
{
    protected $table = 'master_modules';

    protected $fillable = [
        'name',
        'main_menu_id',
        'sub_menu_id',
        'customer_type_id',
        'platform',
        'order_by',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function main_menu()
    {
        return $this->belongsTo(MainMenu::class, 'main_menu_id');
    }

    public function sub_menu()
    {
        return $this->belongsTo(SubMenu::class, 'sub_menu_id');
    }
}
