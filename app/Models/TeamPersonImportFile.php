<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamPersonImportFile extends Model
{
    protected $table = 'team_person_import_file';
    protected $fillable = [
        'company_id',
        'filename',
        'total_team_persons',
        'total_success_team_persons',
        'total_failed_team_persons',
        'errors',
        'created_by',
        'updated_by',
        'deleted_by',
    ];
}
