<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inv_imagenes extends Model
{
    use HasFactory;
    protected $table = 'inv_imagenes';
    protected $primaryKey = 'idLista';  
    public $incrementing = true;
    public $timestamps = true; 
}