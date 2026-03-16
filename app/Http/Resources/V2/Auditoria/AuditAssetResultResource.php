<?php

namespace App\Http\Resources\V2\Auditoria;

use App\Http\Resources\V1\CrudActivoLiteResource;
use App\Models\CrudActivo;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditAssetResultResource extends JsonResource
{

    protected array $auditStatuses = [
        ['id' => CrudActivo::AUDIT_STATUS_COINCIDENTE, 'name' => 'Coincidente', 'class' => 'color-celeste text-white px-2 text-sm mb-1 bg-pink-500'],
        ['id' => CrudActivo::AUDIT_STATUS_FALTANTE, 'name' => 'Faltante', 'class' => 'color-magenta text-white px-2 text-sm mb-1 bg-cyan-500'],
        ['id' => CrudActivo::AUDIT_STATUS_SOBRANTE, 'name' => 'Sobrante', 'class' => 'color-fucsia text-white px-2 text-sm mb-1 bg-red-600']
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $asset = CrudActivo::where('etiqueta', $this['etiqueta'])->firstOrFail();



        if (!$asset) {
            return [
                'etiqueta' => $this['etiqueta'],
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
                'audit_status' => $this['audit_status'],
                'status_scan_id' => $this['audit_status'],
                'status_scan_name' => $this->getAuditStatusNameByID((int)$this['audit_status']),
                'status_scan_extra_class' => $this->getExtraClassAuditStatus((int)$this['audit_status']),
                'descripcionCategoria' => '',
                'descripcionFamilia' => 'Indeterminado',
                'descripcion_grupo' => 'Indeterminado',
                'fotoUrl' => '',
            ];
        }

        $asset->status_scan_id = $this['audit_status'];
        $asset->status_scan_name = $this->getAuditStatusNameByID((int)$this['audit_status']);
        $asset->status_scan_extra_class = $this->getExtraClassAuditStatus((int)$this['audit_status']);

        return CrudActivoLiteResource::make($asset);
    }

    protected function getExtraClassAuditStatus(int $audit_status_id)
    {
        // status_scan_id ?: number;
        // status_scan_name ?: string;
        // status_scan_extra_class ?: string;

        return collect($this->auditStatuses)->where('id', $audit_status_id)->pluck('class')->first();
    }

    protected function getAuditStatusNameByID(int $audit_status_id)
    {
        return collect($this->auditStatuses)->where('id', $audit_status_id)->pluck('name')->first();
    }
}
