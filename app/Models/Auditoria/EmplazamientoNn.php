<?php

namespace App\Models\Auditoria;

use App\Models\CrudActivo;
use App\Models\Inv_ciclos_categorias;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EmplazamientoNn extends Model
{
    use HasFactory;

    protected $fillable = [
        'idUbicacionN1',
        'idUbicacionN2',
        'idUbicacionN3',
        'idUbicacionN4',
        'idUbicacionN5',
        'idUbicacionN6',
        'idProyecto',
        'idAgenda',
        'codigoUbicacion',
        'descripcionUbicacion',
        'estado',
        'usuario',
        'ciclo_auditoria',
        'newApp',
        'modo'
    ];

    const CREATED_AT = 'fechaCreacion';
    const UPDATED_AT = 'fechaActualizacion';

    public static function fromTable($table)
    {
        $instance = new static;
        $instance->setTable($table);
        return $instance;
    }

    public function activos()
    {
        $nextnivel = (int)substr($this->table, -1) + 1;
        if ($nextnivel > 6) {
            return $this->activos_without_join();
        }
        return $this->activos_with_child_levels()->whereRaw('LENGTH(ubicacionOrganicaN' . $nextnivel . ') < 2');
    }




    public function activos_with_child_levels()
    {

        return $this->activos_without_join();
    }

    public function activos_without_join()
    {
        $nivel = substr($this->table, -1);
        return CrudActivo::where('ubicacionGeografica', '=', $this->idAgenda)->where('ubicacionOrganicaN' . $nivel, '=', $this->codigoUbicacion);
    }


    public function activos_with_cats_by_cycle($cycle_id)
    {


        $queryBuilder = $this->activos();

        $queryBuilder =  $this->queryBuilderJoinAuditSubplaceGroupFamily($queryBuilder, $cycle_id);

        return $queryBuilder;
    }

    public function activos_with_cats_with_child_levels_by_cycle($cycle_id)
    {


        $queryBuilder = $this->activos_with_child_levels();

        $queryBuilder =  $this->queryBuilderJoinAuditSubplaceGroupFamily($queryBuilder, $cycle_id);

        return $queryBuilder;
    }







    public function crud_audit_group_families($cycle_id)
    {
        $queryBuilder = $this->crud_audit_group_families_with_child_levels($cycle_id);

        $nextnivel = (int)substr($this->table, -1) + 1;
        if ($nextnivel > 6) {
            return $queryBuilder;
        }
        return $queryBuilder->whereRaw('LENGTH(ubicacionOrganicaN' . $nextnivel . ') < 2');
    }

    public function crud_audit_group_families_with_child_levels($cycle_id)
    {
        $idUbicacionGeo = $this->idAgenda;

        $nivel = substr($this->table, -1);

        $queryBuilder = CrudActivo::select(
            DB::raw("ubicacionOrganicaN" . $nivel . " AS codigoUbicacion"),
            DB::raw("'n" . $nivel . "' AS place_level"),
            DB::raw("'1' AS isSub"),
            'crud_activos.id_grupo',
            'crud_activos.id_familia',
            'dp_grupos.descripcion_grupo',
            'dp_familias.descripcion_familia',
            DB::raw('COUNT(*) as total')
        );

        $queryBuilder =  $this->queryBuilderJoinAuditSubplaceGroupFamily($queryBuilder, $cycle_id);
        $queryBuilder = $queryBuilder->leftJoin('dp_familias', 'crud_activos.id_familia', 'dp_familias.id_familia')
            ->leftJoin('dp_grupos', 'crud_activos.id_grupo', 'dp_grupos.id_grupo')
            ->where('crud_activos.ubicacionGeografica', '=', $idUbicacionGeo)
            ->where('crud_activos.ubicacionOrganicaN' . $nivel, '=', $this->codigoUbicacion)
            ->groupBy('codigoUbicacion', 'crud_activos.id_grupo', 'crud_activos.id_familia', 'dp_grupos.descripcion_grupo', 'dp_familias.descripcion_familia');

        return $queryBuilder;
    }


    /**
     * Query Builder Auditing for this address.
     *
     * @param   \Illuminate\Database\Eloquent\Builder $queryBuilder 
     * @param   int $cycle_id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function queryBuilderJoinAuditSubplaceGroupFamily($queryBuilder, int $cycle_id)
    {

        $familias = Inv_ciclos_categorias::where('idCiclo', $cycle_id)->get()->pluck('id_familia')->toArray();
        //$puntos = InvCicloPunto::where('idCiclo', $cycle_id)->get()->pluck('idPunto')->toArray();

        return $queryBuilder->whereIn('crud_activos.id_familia', $familias);
        //->whereIn('crud_activos.ubicacionGeografica', $puntos);
    }
}
