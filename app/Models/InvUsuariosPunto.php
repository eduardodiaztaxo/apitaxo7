<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvUsuariosPunto extends Model
{
    use HasFactory;

    protected $table = 'inv_usuarios_puntos';

    public function ciclo()
    {
        return $this->belongsTo(InvCiclo::class, 'idCiclo', 'idCiclo');
    }
}
