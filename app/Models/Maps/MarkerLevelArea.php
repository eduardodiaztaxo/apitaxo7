<?php

namespace App\Models\Maps;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarkerLevelArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'marker_id',
        'area_id',
        'level'
    ];

    protected $table = 'map_markers_levels_areas';
}
