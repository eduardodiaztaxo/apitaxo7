<?php

namespace App\Http\Resources\V1;

use App\Services\ActivoService;
use App\Services\ImageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Resources\Json\JsonResource;

class CrudActivoLiteResource extends JsonResource
{
    private $activoService;
    private $cycle_id;
    private $idAgenda;

    /**
     * Create a new resource instance.
     *
     * @param  mixed  $resource
     * @return void
     */
    public function __construct($resource, $cycle_id = null, $idAgenda = null)
    {
        $this->activoService = new ActivoService();
        $this->cycle_id = $cycle_id;
        $this->idAgenda = $idAgenda;
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $marcaResult = [];
        if (!empty($this->marca) && !empty($this->id_familia)) {
            $marcaResult = DB::select("SELECT descripcion 
                FROM `indices_listas`
                WHERE idLista = :idLista
                AND id_familia = :idFamilia
                AND idAtributo = :idAtributo", [
                'idLista' => $this->marca,
                'idFamilia' => $this->id_familia,
                'idAtributo' => 2,
            ]);
        }
        $grupoDescripcion = DB::table('dp_grupos')
            ->where('id_grupo', $this->id_grupo)
            ->value('descripcion_grupo');

        $activo['marca'] = !empty($marcaResult) ? $marcaResult[0]->descripcion : 'Sin marca';

        $activo = [];
        $activo['etiqueta'] = $this->etiqueta;
        $activo['categoriaN3'] = $this->categoriaN3;
        $activo['id_familia'] = $this->id_familia;
        $activo['id_grupo'] = $this->id_grupo;
        $activo['nombreActivo'] = $this->nombre_activo_origen ?? $this->nombreActivo;
        $activo['modelo'] = $this->modelo;
        $activo['serie'] = $this->serie;
        $activo['marca'] = !empty($marcaResult) ? $marcaResult[0]->descripcion : '';

        $activo['ubicacionOrganicaN2'] = $this->ubicacionOrganicaN2;

        $auditStatus = $this->audit_status;

        $activo['id_ciclo'] = $this->cycle_id;
        $activo['id_agenda'] = $this->idAgenda;





        $imagenes = DB::table('crud_activos_pictures')
            ->where('etiqueta', $this->etiqueta)
            ->select(['url_imagen', 'url_picture', 'picture'])
            ->get()
            ->map(function ($foto) {
                return [
                    'original_url' => ImageService::buildOriginalUrl($foto->url_imagen, $foto->url_picture, $foto->picture),
                    'thumb_url' => ImageService::buildThumbnailUrl($foto->url_picture, $foto->picture),
                ];
            })
            ->filter()
            ->values()
            ->toArray();

        $originalUrls = array_values(array_unique(array_filter(array_map(fn ($foto) => $foto['original_url'] ?? null, $imagenes))));
        $thumbUrls = array_values(array_unique(array_filter(array_map(fn ($foto) => $foto['thumb_url'] ?? null, $imagenes))));
        $primaryThumbUrl = $thumbUrls[0] ?? null;

        $fotoUrl = $originalUrls[0] ?? asset('img/notavailable.jpg');







        $activo['status_scan_id'] = $auditStatus ?? 2;
        $activo['status_scan_name'] = $this->status_scan_name ?? 'Sin auditar';
        $activo['status_scan_extra_class'] = $this->status_scan_extra_class ?? 'status-scan-default';

        $activo['descripcionCategoria'] = $this->categoria ? $this->categoria->descripcionCategoria : '';

        $activo['descripcionFamilia'] = $this->familia ? $this->familia->descripcion_familia : '';
        $activo['descripcion_grupo'] = $grupoDescripcion ?? '';
        //$activo['fotoUrl'] = $this->activoService->getUrlAsset($this->resource, $request->user());
        $activo['fotoUrl'] = $fotoUrl;
        $activo['originalUrl'] = $originalUrls[0] ?? $fotoUrl;
        $activo['thumbUrl'] = $primaryThumbUrl ?? $fotoUrl;
        $activo['imagenes'] = $originalUrls;
        $activo['thumbnails'] = $thumbUrls;

        return $activo;
    }
}
