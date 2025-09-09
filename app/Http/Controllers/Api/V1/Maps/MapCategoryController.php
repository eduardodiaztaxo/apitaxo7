<?php

namespace App\Http\Controllers\Api\V1\Maps;

use App\Http\Controllers\Controller;
use App\Models\Maps\MapCategory;
use Illuminate\Http\Request;

class MapCategoryController extends Controller
{
    //

    public function index()
    {
        $categories = MapCategory::all();

        return response()->json($categories);
    }
}
