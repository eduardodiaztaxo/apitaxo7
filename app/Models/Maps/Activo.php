<?php
namespace App\Models\Maps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activo extends Model
{
    use HasFactory;
    protected $table = 'map_crud_activos';
    protected $primaryKey = 'id_inventario';
    public $timestamps = false;

    protected $fillable = [
        'id_inventario','id_proyecto','id_grupo','id_familia','descripcion_bien',
        'id_bien','descripcion_marca','id_marca','idForma','idMaterial','etiqueta',
        'etiqueta_padre','modelo','serie','capacidad','estado','color','tipo_trabajo',
        'carga_trabajo','estado_operacional','estado_conservacion','condicion_Ambiental',
        'eficiencia',
        'texto_abierto_1','texto_abierto_2','texto_abierto_3','texto_abierto_4','texto_abierto_5',
        'texto_abierto_6','texto_abierto_7','texto_abierto_8','texto_abierto_9','texto_abierto_10',
        'texto_abierto_11','texto_abierto_12','texto_abierto_13','texto_abierto_14','texto_abierto_15',
        'texto_abierto_16','texto_abierto_17','texto_abierto_18','texto_abierto_19','texto_abierto_20',
        'texto_abierto_21','texto_abierto_22','texto_abierto_23','texto_abierto_24','texto_abierto_25',
        'texto_abierto_26','texto_abierto_27','texto_abierto_28','texto_abierto_29','texto_abierto_30',
        'texto_abierto_31','texto_abierto_32','texto_abierto_33','texto_abierto_34','texto_abierto_35',
        'texto_abierto_36','texto_abierto_37','texto_abierto_38','texto_abierto_39','texto_abierto_40',
        'texto_abierto_41','texto_abierto_42','texto_abierto_43','texto_abierto_44','texto_abierto_45',
        'texto_abierto_46','texto_abierto_47','texto_abierto_48','texto_abierto_49','texto_abierto_50',
        'cantidad_img','id_img','id_ciclo','idUbicacionGeo','codigoUbicacion_N1',
        'idUbicacionN2','codigoUbicacion_N2','idUbicacionN3','codigoUbicacionN3',
        'codigoUbicacionN4','codigoUbicacionN5','codigoUbicacionN6',
        'responsable','idResponsable','latitud','longitud',
        'adjusted_lat','adjusted_lng','adjusted_at','adjusted_by','adjusted_origin',
        'fix_quality','satellites','sd_lat','sd_lon',
        'descripcionTipo','observacion','update_inv','creado_por','creado_el',
        'modificado_el','modificado_por','crud_activo_estado','modo',
    ];
}