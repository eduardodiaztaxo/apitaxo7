<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecUserCargo extends Model
{
    use HasFactory;

    protected $table = 'sec_users_cargos';

    protected $primaryKey = 'idCargo';

    public $timestamps = false;
}
