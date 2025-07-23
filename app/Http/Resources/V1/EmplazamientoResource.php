<?php

namespace App\Http\Resources\V1;

use Illuminate\Support\Facades\DB;
use App\Http\Resources\V1\CrudActivoLiteResource;
use App\Http\Resources\V1\crudActivoInventarioResource;
use App\Models\InvConteoRegistro;
use App\Models\Inv_ciclos_categorias;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class EmplazamientoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $activosCollection = $this->activos()
            ->select(
                'crud_activos.etiqueta',
                'crud_activos.categoriaN3',
                'crud_activos.id_familia',
                'crud_activos.id_grupo',
                'crud_activos.nombreActivo',
                'crud_activos.idIndice',
                DB::raw("COALESCE(CONCAT(crud_activos_pictures.url_picture, '/', crud_activos_pictures.picture), 'https://api.taxochile.cl/img/notavailable.jpg') AS foto4")
            )
             ->where('crud_activos.tipoCambio', '<>', 200) //EXCLUIR tipoCambio INVENTARIO
            ->leftJoin(DB::raw('(
            SELECT id_foto, id_activo, url_picture, picture
            FROM crud_activos_pictures
            WHERE (id_foto, id_activo) IN (
                SELECT MAX(id_foto), id_activo
                FROM crud_activos_pictures
                GROUP BY id_activo
            )
        ) as crud_activos_pictures'), 'crud_activos_pictures.id_activo', '=', 'crud_activos.idActivo')
            ->get();

        $activosInventario = DB::table('inv_inventario')
            ->leftJoin('categoria_n3', 'inv_inventario.id_familia', '=', 'categoria_n3.id_familia')
            ->leftJoin('inv_imagenes', 'inv_inventario.id_img', '=', 'inv_imagenes.id_img')
            ->where('inv_inventario.idUbicacionN2', $this->idUbicacionN2)
            ->where('inv_inventario.id_ciclo', $this->cycle_id)
            ->select(
                'inv_inventario.id_ciclo',
                'inv_inventario.id_inventario',
                'inv_inventario.etiqueta',
                'categoria_n3.codigoCategoria',
                'inv_inventario.id_familia',
                'inv_inventario.id_grupo',
                'inv_inventario.descripcion_bien',
                'inv_inventario.modelo',
                'inv_inventario.serie',
                'inv_inventario.descripcion_marca',
                'inv_inventario.idUbicacionN2',
                'inv_inventario.update_inv',
                'categoria_n3.descripcionCategoria',
                DB::raw('MIN(inv_imagenes.url_imagen) as url_imagen')
            )
            ->groupBy(
                'inv_inventario.id_ciclo',
                'inv_inventario.id_inventario',
                'inv_inventario.etiqueta',
                'categoria_n3.codigoCategoria',
                'inv_inventario.id_familia',
                'inv_inventario.id_grupo',
                'inv_inventario.descripcion_bien',
                'inv_inventario.modelo',
                'inv_inventario.serie',
                'inv_inventario.descripcion_marca',
                'inv_inventario.idUbicacionN2',
                'inv_inventario.update_inv',
                'categoria_n3.descripcionCategoria'
            )
            ->get();


        $activosInventario = $activosInventario->map(function ($activo) {
            $firstImageUrl = "https://api.taxochile.cl/img/notavailable.jpg"; // URL por defecto

            if (!empty($activo->url_imagen)) {

                $folderPath = str_replace('http://apitaxo7.cl/storage/', '', $activo->url_imagen);
                $localFolderPath = public_path('storage/' . $folderPath);

                if (is_dir($localFolderPath)) {
                    $files = scandir($localFolderPath);

                    $imageFiles = array_filter($files, function ($file) {
                        return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png']);
                    });

                    if (!empty($imageFiles)) {
                        $firstImageUrl = asset('storage/' . $folderPath . '/' . reset($imageFiles));
                    }
                }
            }
         $familiaDescripcion = DB::table('dp_familias')
        ->where('id_familia', $this->id_familia)
        ->value('descripcion_familia');

            return (object)[
                'id_ciclo' => $this->cycle_id,
                'id_inventario' => $activo->id_inventario,
                'etiqueta' => $activo->etiqueta,
                'categoriaN3' => $activo->codigoCategoria,
                'id_familia' => $activo->id_familia,
                'id_grupo' => $activo->id_grupo,
                'nombreActivo' => $activo->descripcion_bien,
                'modelo' => $activo->modelo ?? '',
                'serie' => $activo->serie ?? '',
                'marca' => $activo->descripcion_marca ?? null,
                'ubicacionOrganicaN2' => $activo->idUbicacionN2,
                'update_inv' => $activo->update_inv,
                'categoria' => null,
                'familia' => null,
                'descripcionCategoria' => $activo->descripcionCategoria,
                'descripcionFamilia' => $familiaDescripcion ?? null,
                'fotoUrl' => $firstImageUrl,
            ];
        });

        $emplazamiento = [
            'id' => $this->idUbicacionN2,
            'codigo' => $this->codigo,
            'codigoUbicacion' => $this->codigoUbicacion,
            'nombre' => $this->descripcionUbicacion,
            'idAgenda' => $this->idAgenda,
            'idUbicacionN2' => $this->idUbicacionN2,
            'num_activos' => 0,
            'num_activos_inv' => $activosInventario->count(),
            'num_activos_cats_by_cycle' => 0,
            'ciclo_auditoria' => $this->ciclo_auditoria,
            'num_categorias' => $this->activos()->select('categoriaN3')->groupBy('categoriaN3')->get()->count(),
            'id_ciclo' => $this->cycle_id,
            'zone_address' => ZonaPuntoResource::make($this->zonaPunto()->first())
        ];

        if (isset($this->requirePunto) && $this->requirePunto) {
            $emplazamiento['ubicacionPunto'] = UbicacionGeograficaResource::make($this->ubicacionPunto()->first());
        }
        if (isset($this->requireActivos) && $this->requireActivos) {

            $categorias = Inv_ciclos_categorias::where('idCiclo', $this->cycle_id)
                ->pluck('id_grupo')
                ->unique()
                ->values()
                ->toArray();

            if (isset($this->cycle_id) && $this->cycle_id) {
                $activosByCycle = $this->activos_with_cats_by_cycle($this->cycle_id, $this->idAgenda)
                    ->whereIn('crud_activos.id_grupo', $categorias)
                    ->get()
                    ->map(function ($activo) {
                        return (new CrudActivoLiteResource($activo, $this->cycle_id, $this->idAgenda))->toArray(request());
                    });

                $emplazamiento['num_activos'] = $activosByCycle->count();

                // Verificar si está vacío
                if ($activosByCycle->isEmpty()) {

                    $idsGrupos = DB::select("
                SELECT 
                    dp_grupos.descripcion_grupo,
                    dp_familias.descripcion_familia,
                    dp_familias.id_grupo,
                    dp_familias.id_familia
                FROM dp_grupos
                INNER JOIN dp_familias 
                    ON dp_grupos.id_grupo = dp_familias.id_grupo
                LEFT JOIN inv_ciclos_categorias 
                    ON inv_ciclos_categorias.id_familia = dp_familias.id_familia
                AND inv_ciclos_categorias.id_grupo = dp_familias.id_grupo
                AND inv_ciclos_categorias.idCiclo = ?
                WHERE IFNULL(inv_ciclos_categorias.id_familia, '0') <> '0'
                ORDER BY dp_grupos.descripcion_grupo, dp_familias.descripcion_familia
            ", [$this->cycle_id]);

                    $ids = collect($idsGrupos)->pluck('id_grupo')->unique()->values()->toArray();

                    $activosInventarioFiltrados = $activosInventario->whereIn('id_grupo', $ids);

                    $activosInventarioArray = $activosInventarioFiltrados->map(function ($activo) {
                        return (new crudActivoInventarioResource($activo))->toArray(request());
                    })->toArray();

                    $emplazamiento['activos'] = $activosInventarioArray;
                    $emplazamiento['num_activos'] = count($emplazamiento['activos']);
                } else {
                    $emplazamiento['activos'] = $activosByCycle;
                }
            }
        }

        if (isset($this->cycle_id) && $this->cycle_id) {
            $emplazamiento['num_activos_audit'] = InvConteoRegistro::where('ciclo_id', '=', $this->cycle_id)
                ->where('cod_emplazamiento', '=', $this->codigoUbicacion)
                ->whereIn('audit_status', [1, 3])
                ->count();
            $emplazamiento['num_activos_cats_by_cycle'] = isset($emplazamiento['activos']) ? count($emplazamiento['activos']) : $this->activos_with_cats_by_cycle($this->cycle_id)->count();
        }

        return $emplazamiento;
    }
}
