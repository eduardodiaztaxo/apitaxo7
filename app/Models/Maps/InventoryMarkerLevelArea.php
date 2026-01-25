<?php

namespace App\Models\Maps;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMarkerLevelArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_id',
        'area_id',
        'level'
    ];

    protected $table = 'map_inventory_levels_areas';
}
