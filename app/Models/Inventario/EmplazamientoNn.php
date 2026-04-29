<?php

namespace App\Models\Inventario;

use App\Models\Inventario;
use App\Models\UbicacionGeografica;
use App\Models\ZonaPunto;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
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


    public function inv_activos()
    {
        $nextLevel = $this->getNextLevel();

        $queryBuilder =  $this->inv_activos_with_child_levels();

        if ($nextLevel != -1) {
            $field_codeUbicacion = $this->getFieldcodigoUbicacionInvByLevel($nextLevel);
            $queryBuilder = $queryBuilder->whereRaw('LENGTH(inv_inventario.' . $field_codeUbicacion . ') < 2');
        }
        return $queryBuilder;
    }

    public function inv_activos_with_child_levels()
    {

        $field_ubicacion = $this->getFieldcodigoUbicacionInv();

        return $this->hasMany(Inventario::class, 'idUbicacionGeo', 'idAgenda')
            ->where('inv_inventario.' . $field_ubicacion, $this->codigoUbicacion);
    }

    public function inv_group_families()
    {

        $nextLevel = $this->getNextLevel();

        $queryBuilder =  $this->inv_group_families_with_child_levels();

        if ($nextLevel != -1) {
            $field_codeUbicacion = $this->getFieldcodigoUbicacionInvByLevel($nextLevel);
            $queryBuilder = $queryBuilder->whereRaw('LENGTH(inv_inventario.' . $field_codeUbicacion . ') < 2');
        }
        return $queryBuilder;
    }

    public function inv_group_families_with_child_levels()
    {

        $level = $this->getSubnivel();

        $table = $this->getTable();

        $fieldCodigoUbicacion = $this->getFieldcodigoUbicacionInv();

        $idAgenda = $this->idAgenda;
        $codigoUbicacion = $this->codigoUbicacion;


        return Inventario::select(
            $table . '.codigoUbicacion',
            DB::raw("'n$level' AS place_level"),
            DB::raw("'1' AS isSub"),
            'inv_inventario.id_ciclo',
            'inv_inventario.id_grupo',
            'inv_inventario.id_familia',
            'dp_grupos.descripcion_grupo',
            'dp_familias.descripcion_familia',
            DB::raw('COUNT(*) as total')
        )->join($table, function (JoinClause $join) use ($table, $fieldCodigoUbicacion) {

            $join->on('inv_inventario.idUbicacionGeo', '=', $table . '.idAgenda')
                ->on('inv_inventario.' . $fieldCodigoUbicacion, '=', $table . '.codigoUbicacion');
        })
            ->join('dp_familias', 'inv_inventario.id_familia', 'dp_familias.id_familia')
            ->join('dp_grupos', 'inv_inventario.id_grupo', 'dp_grupos.id_grupo')
            ->where($table . '.idAgenda', '=', $idAgenda)->where($table . '.codigoUbicacion', '=', $codigoUbicacion)
            ->groupBy($table . '.codigoUbicacion', 'inv_inventario.id_ciclo', 'inv_inventario.id_grupo', 'inv_inventario.id_familia', 'dp_grupos.descripcion_grupo', 'dp_familias.descripcion_familia');
    }

    public function zonaPunto()
    {
        return ZonaPunto::where('idAgenda', $this->idAgenda)
            ->where('codigoUbicacion', substr($this->codigoUbicacion, 0, 2));
    }

    public function ubicacionPunto()
    {
        return $this->belongsTo(UbicacionGeografica::class, 'idAgenda', 'idUbicacionGeo');
    }

    public function nextCode(int $idAgenda, string $parentCode): string
    {
        $idMax = $this->where('idAgenda', $idAgenda)
            ->where('codigoUbicacion', 'like', $parentCode . '%')
            ->max('codigoUbicacion');



        if ($idMax) {
            $num = substr($idMax, strlen($parentCode));
            $numIncrementado = str_pad((int)$num + 1, 2, '0', STR_PAD_LEFT);
            $code = $parentCode . $numIncrementado;
        } else {
            $code = $parentCode . '01';
        }
        return $code;
    }

    public function getFieldcodigoUbicacionInv()
    {

        $level = $this->getSubnivel();

        return $this->getFieldcodigoUbicacionInvByLevel($level);
    }

    private function getFieldcodigoUbicacionInvByLevel($level)
    {
        if ($level < 3) {
            return 'codigoUbicacion_N' . $level;
        }
        return 'codigoUbicacionN' . $level;
    }

    public function getSubnivel()
    {
        $table = $this->getTable();
        return (int)substr($table, -1);
    }

    public function getNextLevel()
    {
        $currentLevel = $this->getSubnivel();
        if ($currentLevel < 6) {
            return ($currentLevel + 1);
        }

        return -1; // No hay siguiente nivel
    }
}
