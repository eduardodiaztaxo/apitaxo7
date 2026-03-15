<?php

namespace App\Http\Resources\V2\Auditoria;

use App\Http\Resources\V1\CrudActivoLiteResource;
use App\Models\CrudActivo;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditAssetResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $asset = CrudActivo::where('etiqueta', $this->etiqueta)->firstOrFail();
        $asset->audit_status = $this->audit_status;

        if (!$asset) {
            return [
                'etiqueta' => $this->etiqueta,
                'categoriaN3' => '',
                'id_familia' => '',
                'id_grupo' => '',
                'nombreActivo' => 'Indeterminado',
                'modelo' => '',
                'serie' => '',
                'marca' => '',
                'ubicacionOrganicaN2' => '',
                'id_ciclo' => '',
                'id_agenda' => '',
                'audit_status' => $this->audit_status,
                'audit_status_name' => $this->audit_status === CrudActivo::AUDIT_STATUS_SOBRANTE ? 'Sobrante' : ($this->audit_status === CrudActivo::AUDIT_STATUS_FALTANTE ? 'Faltante' : 'Sin auditar'),
                'descripcionCategoria' => '',
                'descripcionFamilia' => '',
                'descripcion_grupo' => '',
                'fotoUrl' => '',
            ];
        }

        return CrudActivoLiteResource::make($asset);
    }
}
