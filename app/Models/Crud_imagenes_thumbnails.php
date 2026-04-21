<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Crud_imagenes_thumbnails extends Model
{
    use HasFactory;

    protected $table = 'crud_imagenes_thumbnails';
    protected $primaryKey = 'idLista';
    public $incrementing = true;
    public $timestamps = true;

    protected $guarded = [];
}
