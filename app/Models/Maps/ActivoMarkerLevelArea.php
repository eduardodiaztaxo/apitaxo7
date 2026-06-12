<?php
namespace App\Models\Maps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivoMarkerLevelArea extends Model
{
    use HasFactory;
    protected $table = 'map_activos_levels_areas';
    protected $fillable = ['activo_id', 'area_id', 'level'];
}