<?php

namespace App\Models\Maps;

use App\Models\UbicacionGeografica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapPolygonalArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'area',
        'parent_id',
        'level',
        'min_lat',
        'max_lat',
        'min_lng',
        'max_lng',
        'total_markers',
        'total_markers_at'
    ];

    public function markers()
    {
        $markers = MapMarkerAsset::where('lat', '>=', $this->min_lat)
            ->where('lat', '<=', $this->max_lat)
            ->where('lng', '>=', $this->min_lng)
            ->where('lng', '<=', $this->max_lng)
            ->get();

        return $markers->filter(function ($marker) {
            return $this->isPointInsidePolygon($marker->lat, $marker->lng, json_decode($this->area, true));
        });
    }

    /** 
     * Get the last photo of the markers in the area
     * 
     */
    public function markersLastPhoto()
    {
        return $this->hasMany(MarkerLevelArea::class, 'area_id', 'id')
            ->where('level', $this->level);
    }


    /** 
     * Get the last photo of the markers in the area
     * 
     */
    public function punto()
    {
        return $this->belongsTo(UbicacionGeografica::class, 'address_id', 'idUbicacionGeo');
    }

    function isPointInsidePolygon($lat, $lng, $polygon)
    {
        $inside = false;
        $j = count($polygon) - 1;

        for ($i = 0; $i < count($polygon); $i++) {
            $xi = $polygon[$i]['lat'];
            $yi = $polygon[$i]['lng'];
            $xj = $polygon[$j]['lat'];
            $yj = $polygon[$j]['lng'];

            $intersect = (($yi > $lng) != ($yj > $lng)) &&
                ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi);
            if ($intersect) {
                $inside = !$inside;
            }

            $j = $i;
        }

        return $inside;
    }

    public function updateMinMaxLatLng()
    {

        $min_lat = null;
        $max_lat = null;
        $min_lng = null;
        $max_lng = null;

        $area = json_decode($this->area, true);
        //ojo con los polígonos que pasan desde -180 hacia más a la izquierda
        //y desde 180 hacia más a la derecha, ya que pueden tener coordenadas negativas
        //y positivas, por lo que se debe calcular el mínimo y máximo de latitud y
        //longitud de forma adecuada.
        if (is_array($area) && count($area) > 0) {

            $min_lat = min(array_column($area, 'lat'));
            $max_lat = max(array_column($area, 'lat'));
            $min_lng = min(array_column($area, 'lng'));
            $max_lng = max(array_column($area, 'lng'));

            $min_lat = number_format((float)$min_lat, 14, '.', '');
            $max_lat = number_format((float)$max_lat, 14, '.', '');
            $min_lng = number_format((float)$min_lng, 14, '.', '');
            $max_lng = number_format((float)$max_lng, 14, '.', '');
        }



        $mapAreaArr = [
            'min_lat' => $min_lat,
            'max_lat' => $max_lat,
            'min_lng' => $min_lng,
            'max_lng' => $max_lng,
        ];

        $this->update($mapAreaArr);
    }
}
