<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvCicloUser extends Model
{
    use HasFactory;

    protected $table = 'inv_ciclos_usuarios';

    public function ciclos()
    {
        return $this->hasMany(InvCiclo::class, 'idCiclo', 'ciclo_id');
    }
}
