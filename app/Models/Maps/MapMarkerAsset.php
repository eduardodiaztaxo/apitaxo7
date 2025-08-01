<?php

namespace App\Models\Maps;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapMarkerAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_id',
        'lat',
        'lng',
    ];
}
