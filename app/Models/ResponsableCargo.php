<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResponsableCargo extends Model
{
    use HasFactory;

    protected $table = 'responsables_cargos';

    protected $primaryKey = 'id';

    public $timestamps = false;
}
