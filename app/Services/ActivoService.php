<?php

namespace App\Services;

use App\Models\CrudActivo;
use App\Models\Emplazamiento;
use App\Models\InvCiclo;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ActivoService
{

    /**
     * Get URL image from assets; user give data for doing path.
     *
     * @param  \App\Models\CrudActivo $activo 
     * @param  \App\Models\User  $user
     * @return string
     */
    public function getUrlAsset(CrudActivo $activo, User $user): string
    {

        if ($activo->foto4) {
            return asset('storage/' . $activo->foto4);
        }


        $url = url('img/notavailable.jpg');


        $proyecto_id = $user->proyecto_id;

        $nombre_foto = $activo->foto1;

        $nombre_cliente = $user->nombre_cliente;

        $numero_etiqueta = $activo->etiqueta;

        #CLIENTE
        $url_cliente      = 'https://cloud.taxochile.cl/sys/preproduccion/_lib/file/img/' . $nombre_cliente . '/activo/' . $nombre_foto;
        #LEVANTAMIENTO
        $url_levanta_1  = 'https://files.taxochile.cl/PROCESADAS/' . $proyecto_id . '/' . $proyecto_id . '_' . $numero_etiqueta . '.PNG';
        $url_levanta_2     = 'https://files.taxochile.cl/PROCESADAS/' . $proyecto_id . '/' . $proyecto_id . '_' . $numero_etiqueta . '.png';

        $url1  = $this->urlCheck($url_cliente);         # Subida por el cliente
        $url2  = $this->urlCheck($url_levanta_1);      # Foto del Levantamiento
        $url2a = $this->urlCheck($url_levanta_2);         # Foto del Levantamiento

        if ($url1 == true && $nombre_foto != '') {

            $url = 'https://cloud.taxochile.cl/sys/preproduccion/_lib/file/img/' . $nombre_cliente . '/activo/' . $nombre_foto;
        } elseif ($url2 == true || $url2a == true) {

            $url = 'https://files.taxochile.cl/PROCESADAS/' . $proyecto_id . '/' . $proyecto_id . '_' . $numero_etiqueta . '.PNG';
        }

        return $url;
    }

    protected function urlCheck($url)
    {

        @$headers = get_headers($url);
        if (preg_match('/^HTTP\/\d\.\d\s+(200|301|302)/', $headers[0])) {
            return true;
        } else return false;
    }

    /**
     * Gets labels by place and cycle cats
     * 
     * @param \App\Models\Emplazamiento $empObj
     * @param \App\Models\InvCiclo $cicloObj
     * @return \Illuminate\Support\Collection
     */
    public static function getLabelsByCycleAndEmplazamiento(Emplazamiento $empObj, InvCiclo $cicloObj)
    {



        $cats_ids = $cicloObj->getCatsIDs();

        $etiquetas = $empObj->activos()
            ->whereIn('categoriaN1', $cats_ids->pluck('categoria1'))
            ->whereIn('categoriaN2', $cats_ids->pluck('categoria2'))
            ->whereIn('categoriaN3', $cats_ids->pluck('categoria3'))
            ->get()->pluck('etiqueta');



        return $etiquetas;
    }
}
