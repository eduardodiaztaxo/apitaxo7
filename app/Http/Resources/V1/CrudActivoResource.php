<?php

namespace App\Http\Resources\V1;

use App\Services\ActivoService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CrudActivoResource extends JsonResource
{


    private $activoService;
    private $ciclo_obj;
    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource, $ciclo = null)
    {
        $this->activoService = new ActivoService();
        parent::__construct($resource);
        $this->ciclo_obj = $ciclo;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $activo = parent::toArray($request);

        $grupoDescripcion = DB::table('dp_grupos')
            ->where('id_grupo', $this->id_grupo)
            ->value('descripcion_grupo');

        $activo['idUbicacionGeografica'] = (int)$activo['ubicacionGeografica'];

        $activo['codigo_activo'] = $this->codigo_activo;

        $activo['tipoAlta'] = $this->tipoAltaRelation ? $this->tipoAltaRelation->descripcion : '';

        $activo['nombreActivo'] = $this->nombre_activo_origen;

        $activo['marca'] = $this->marcaRelation ? $this->marcaRelation->descripcion : '';

        $activo['estadoBien'] = $this->estadoBienRelation ? $this->estadoBienRelation->descripcion : '';

        $activo['descripcionCategoria'] = $this->categoria ? $this->categoria->descripcionCategoria : '';

        $activo['descripcionFamilia'] = $this->familia ? $this->familia->descripcion_familia : '';

        $activo['id_familia'] = $this->id_familia ? $this->id_familia : '';

        $activo['descripcionGrupo'] = $grupoDescripcion;

        $activo['responsable'] = $this->responsable ? $this->responsable->name : null;

        $activo['creado_por'] = $this->responsable ? $this->responsable->name : null;

        $activo['organica_n1'] = $this->zona;

        $activo['organica_n2'] = $this->emplazamiento;

        $activo['depreciable'] = isset($this->depreciableRelation->descripcion) ? $this->depreciableRelation->descripcion : null;


        // Obtener imágenes de crud_activos_pictures
        $imagenes = DB::table('crud_activos_pictures')
            ->where('id_activo', $this->idActivo)
            ->orderByDesc('id_foto')
            ->select(['url_imagen', 'url_picture', 'picture'])
            ->get()
            ->map(function ($foto) {
                return $this->normalizeImageUrl($foto->url_imagen, $foto->url_picture, $foto->picture);
            })
            ->filter()
            ->values()
            ->toArray();

        $activo['fotoUrl'] = $imagenes[0] ?? null;

        if ($request->user() && !$activo['fotoUrl'])
            $activo['fotoUrl'] = $this->activoService->getUrlAsset($this->resource, $request->user());

        $activo['imagenes'] = $imagenes ?? [];
        $activo['tipo_ciclo'] = $this->ciclo_obj ? $this->ciclo_obj->idTipoCiclo : null;

        $ubicacion = $this->ubicacionGeografica()->first();

        if ($this->requireUbicacion && $ubicacion) {
            $activo['ubicacion'] = $ubicacion->toArray();
            $activo['ubicacion']['region'] = $ubicacion->region()->first()->descripcion;
            $activo['ubicacion']['comuna'] = $ubicacion->comuna()->first()->descripcion;
        } else {
            $activo['ubicacion'] = [];
        }

        if (isset($this->requireEmplazamiento) && $this->requireEmplazamiento) {
            $activo['emplazamiento'] = EmplazamientoResource::make($this->emplazamientoZona()->first());
        }


        return $activo;
    }

    /**
     * Normalize the stored picture data into a single usable URL.
     *
     * @param  string|null  $urlImagen
     * @param  string|null  $urlPicture
     * @param  string|null  $picture
     * @return string|null
     */
    private function normalizeImageUrl($urlImagen, $urlPicture, $picture)
    {
        if (!empty($urlImagen) && $urlImagen !== '0') {
            return $urlImagen;
        }

        if (empty($urlPicture) && empty($picture)) {
            return null;
        }

        if (empty($picture)) {
            return $urlPicture;
        }

        if (!empty($urlPicture) && Str::endsWith($urlPicture, $picture)) {
            return $urlPicture;
        }

        return rtrim((string) $urlPicture, '/') . '/' . ltrim((string) $picture, '/');
    }
}
