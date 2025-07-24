<?php

namespace App\Models\Maps;

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
        'max_lng'
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
}
