<?php

namespace App\Http\Resources\V1;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\V1\CrudActivoLiteResource;
use App\Models\InvConteoRegistro;
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
        $activosCollection = $this->activos()->select(
            'etiqueta',
            'categoriaN3',
            'id_familia',
            'nombreActivo',
            'idIndice',
            'foto4'
        )->get();
    
        $activosInventario = DB::table('inv_inventario')
            ->leftJoin('categoria_n3', 'inv_inventario.id_familia', '=', 'categoria_n3.id_familia')
            ->leftJoin('dp_familias', 'inv_inventario.id_familia', '=', 'dp_familias.id_familia')
            ->leftJoin('inv_imagenes', 'inv_inventario.id_img', '=', 'inv_imagenes.id_img')
            ->where('inv_inventario.codigoUbicacion', $this->idUbicacionN2)
            ->select(
                'inv_inventario.etiqueta',
                'categoria_n3.codigoCategoria',
                'inv_inventario.id_familia',
                'inv_inventario.descripcion_bien',
                'categoria_n3.descripcionCategoria',
                'dp_familias.descripcion_familia',
                'inv_imagenes.url_imagen'
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
        
            return [
                'etiqueta' => $activo->etiqueta,
                'categoriaN3' => $activo->codigoCategoria,
                'id_familia' => $activo->id_familia,
                'nombreActivo' => $activo->descripcion_bien,
                'descripcionCategoria' => $activo->descripcionCategoria,
                'descripcionFamilia' => $activo->descripcion_familia,
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
            'num_activos' => $activosCollection->count() + $activosInventario->count(),  
            'num_activos_cats_by_cycle' => 0,
            'ciclo_auditoria' => $this->ciclo_auditoria,
            'num_categorias' => $this->activos()->select('categoriaN3')->groupBy('categoriaN3')->get()->count(),
            'zone_address' => ZonaPuntoResource::make($this->zonaPunto()->first())
        ];
    
        if (isset($this->requirePunto) && $this->requirePunto) {
            $emplazamiento['ubicacionPunto'] = UbicacionGeograficaResource::make($this->ubicacionPunto()->first());
        }
        if (isset($this->requireActivos) && $this->requireActivos) {
            if (isset($this->cycle_id) && $this->cycle_id) {
                $emplazamiento['activos'] = CrudActivoLiteResource::collection($this->activos_with_cats_by_cycle($this->cycle_id)->get());
            } else {
                $activosCollectionArray = CrudActivoLiteResource::collection($activosCollection)->toArray(request());
                $emplazamiento['activos'] = array_merge($activosCollectionArray, $activosInventario->toArray());
            }
        }
        if (isset($this->cycle_id) && $this->cycle_id) {
            $emplazamiento['num_activos_audit'] = InvConteoRegistro::where('ciclo_id', '=', $this->cycle_id)
                ->where('status', '=', '1')
                ->where('cod_emplazamiento', '=', $this->codigoUbicacion)
                ->count();
            $emplazamiento['num_activos_cats_by_cycle'] = isset($emplazamiento['activos']) ? count($emplazamiento['activos']) : $this->activos_with_cats_by_cycle($this->cycle_id)->count();
        }
    
        return $emplazamiento;
    }
}    
