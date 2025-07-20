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
}
