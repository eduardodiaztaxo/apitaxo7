<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UbicacionGeografica extends Model
{
    use HasFactory;

    protected $table = 'ubicaciones_geograficas';

    public function region()
    {
        return $this->belongsTo(Region::class, 'region', 'idRegion');
    }

    public function comuna()
    {
        return $this->belongsTo(Comuna::class, 'comuna', 'idComuna');
    }
}
