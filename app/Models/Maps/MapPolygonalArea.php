<?php

namespace App\Models\Maps;

use App\Models\Inventario;
use App\Models\UbicacionGeografica;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    public function inventory_markers()
    {


        $preMarkers = $this->hasMany(Inventario::class, 'idUbicacionGeo', 'address_id')->get();


        $markers = $preMarkers->map(function ($inventario) {

            return new MapMarkerAsset([
                'inv_id' =>  $inventario->id_inventario,
                'category_id' => $inventario->id_familia,
                'name' => $inventario->descripcion_bien,
                'fix_quality' => $inventario->fix_quality,
                'lat' => $inventario->adjusted_lat ? (float)$inventario->adjusted_lat : (float)$inventario->latitud,
                'lng' => $inventario->adjusted_lng ? (float)$inventario->adjusted_lng : (float)$inventario->longitud,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        });




        return $markers;
    }

    /** 
     * This function get no limit shared areas, what is a no limit shared area? 
     * It is an area that belongs two or more parent areas and not check limits
     * for example, polygon or area level 2 (shared area) can belong to multiple level 1 areas (neighborhoods)
     * This relation is specificied in `map_nolimit_shared_areas` table
     * where `area_id` is the id of the shared area (level 2) and `free_area_id` is the id of the parent area (level 1)
     */
    function nolimit_shared_areas()
    {
        return $this->belongsToMany(
            MapPolygonalArea::class,
            'map_nolimit_shared_areas',
            'area_id',
            'free_area_id'
        );
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


    public static function isCoordinateInsideArea($lat, $lng, $polygon)
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

    function isPointInsidePolygon($lat, $lng, $polygon)
    {
        return self::isCoordinateInsideArea($lat, $lng, $polygon);
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

    public function make_as_nolimit_shared_area($level_parent)
    {
        //Only level 2 areas (shared areas) can be linked as nolimit shared areas
        if($this->level !==2){
            return;
        }

        //Link this area as nolimit shared area to all level 1 areas (free areas)
        $parent_areas = MapPolygonalArea::where('level', $level_parent)->get();

        //First, delete previous links if any
        self::deleteNolimitSharedAreasByFreeAreaId($this->id);

        foreach ($parent_areas as $parent_area) {
            //Link only if this area is inside the parent area
            if ($this->isAreaInsideOrIntersect($parent_area)) {
                
                //Link this area as nolimit shared area to the parent area

                // $attach[] = [
                //     'free_area_id' => $this->id,
                //     'area_id' => $parent_area->id,
                //     'level' => $parent_area->level,
                //     'free_area_level' => $this->level,
                //     'created_at' => now(),
                // ];

                

                DB::table('map_nolimit_shared_areas')->insert([
                    'free_area_id' => $this->id,
                    'area_id' => $parent_area->id,
                    'level' => $parent_area->level,
                    'free_area_level' => $this->level,
                    'created_at' => now(),
                ]);
                
                // $this->nolimit_shared_areas()->attach($this->id, [
                //     'area_id' => $parent_area->id,
                //     'level' => $parent_area->level,
                //     'free_area_level' => $this->level,
                //     'created_at' => now(),
                // ]);
            }
        }


    }

    public static function deleteNolimitSharedAreasByFreeAreaId($free_area_id)
    {
        DB::table('map_nolimit_shared_areas')->where('free_area_id', $free_area_id)->delete();
    }

    /**
     * Check if area is inside another area or intersects with another area
     */
    public function isAreaInsideOrIntersect(MapPolygonalArea $otherArea)
    {
        $thisPolygon = json_decode($this->area, true);
        $otherPolygon = json_decode($otherArea->area, true);

        // Check if any point of this area is inside the other area
        foreach ($thisPolygon as $point) {
            if (self::isCoordinateInsideArea($point['lat'], $point['lng'], $otherPolygon)) {
                return true;
            }
        }

        // Check if any point of the other area is inside this area
        foreach ($otherPolygon as $point) {
            if (self::isCoordinateInsideArea($point['lat'], $point['lng'], $thisPolygon)) {
                return true;
            }
        }

        //Check for edge intersections (not implemented here for simplicity)

        return false;
    }
}
