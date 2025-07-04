<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\IndiceLista;
use Illuminate\Database\Query\JoinClause;

class Inventario extends Model
{
    use HasFactory;

    protected $table = 'inv_inventario';
    protected $primaryKey = 'id_inventario';
    public $timestamps = false;


    protected $fillable = [
        'descripcion_marca',
        'modelo',
        'serie',
        'responsable',
        'estado',
        'descripcionTipo',
        'observacion',
        'latitud',
        'longitud',
    ];
public function marcasDisponibles()
    {

        $queryBuilder = IndiceLista::select('indices_listas.*')
            ->join('categoria_n3', function (JoinClause $join) {
                $join->on('indices_listas.idIndice', '=', 'categoria_n3.idIndice');
            })
            ->join('inv_inventario', 'categoria_n3.idIndice', '=', 'inv_inventario.id_familia')
            ->where('indices_listas.idAtributo', '=', '2')
            ->where('inv_inventario.etiqueta', '=', $this->etiqueta);


        return $queryBuilder;
    }

    
public function imagen()
{
    return $this->belongsTo(Inv_imagenes::class, 'id_img', 'id_img');
}
}