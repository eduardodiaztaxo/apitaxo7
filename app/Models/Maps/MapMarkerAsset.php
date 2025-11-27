<?php

namespace App\Models\Maps;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapMarkerAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'inv_id',
        'name',
        'fix_quality',
        'category_id',
        'lat',
        'lng',
    ];

    public function getCategory()
    {
        return $this->hasOne(MapCategory::class, 'id', 'category_id');
    }
}
