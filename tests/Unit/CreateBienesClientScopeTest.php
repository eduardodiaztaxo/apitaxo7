<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\V1\Comunes\DatosActivosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class CreateBienesClientScopeTest extends TestCase
{
    public function test_other_clients_keep_existing_attribute_validation()
    {
        DB::shouldReceive('connection->getDatabaseName')->once()->andReturn('taxochil_ac-ec-jugos');
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->once()->with(Mockery::on(function ($rules) {
            return $rules['idAtributo'] === 'required|exists:indices_listas,idAtributo';
        }))->andThrow(new \RuntimeException('Existing validation reached'));
        $this->expectExceptionMessage('Existing validation reached');
        (new DatosActivosController())->createBienes($request);
    }

    public function test_index_creation_endpoint_was_removed()
    {
        $routes = collect(app('router')->getRoutes()->getRoutes());
        $this->assertFalse($routes->contains(function ($route) {
            return $route->uri() === 'api/v1/indices-listas';
        }));
        $this->assertFalse(method_exists(DatosActivosController::class, 'createIndiceLista'));
    }
}
