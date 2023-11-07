<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicesLista extends Model
{
    use HasFactory;

    protected $fillable = [
        'idLista',
        'idAtributo',
        'idIndice',
        'descripcion',
    ];

    public $timestamps = false;

}
