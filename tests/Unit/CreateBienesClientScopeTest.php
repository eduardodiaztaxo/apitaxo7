<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\V1\Comunes\DatosActivosController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class CreateBienesClientScopeTest extends TestCase
{
    public function test_ecuessence_initializes_catalogue_and_propagates_failure_before_saving_good()
    {
        DB::shouldReceive('connection->getDatabaseName')->once()->andReturn('taxochil_ac-ecuessence');
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->once()->with(Mockery::on(function ($rules) {
            return $rules['idAtributo'] === 'required|integer|min:1'
                && $rules['ciclo_inventario'] === 'required|exists:inv_ciclos,idCiclo';
        }))->andReturn([]);

        $failure = response()->json(['message' => 'La familia no pertenece al grupo indicado'], 422);
        $controller = Mockery::mock(DatosActivosController::class)->makePartial();
        $controller->shouldReceive('createIndiceLista')->once()->with($request)->andReturn($failure);

        $this->assertSame($failure, $controller->createBienes($request));
    }

    public function test_other_clients_keep_existing_attribute_validation()
    {
        DB::shouldReceive('connection->getDatabaseName')->once()->andReturn('taxochil_ac-ec-jugos');
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('validate')->once()->with(Mockery::on(function ($rules) {
            return $rules['idAtributo'] === 'required|exists:indices_listas,idAtributo';
        }))->andThrow(new \RuntimeException('Existing validation reached'));

        $controller = Mockery::mock(DatosActivosController::class)->makePartial();
        $controller->shouldNotReceive('createIndiceLista');
        $this->expectExceptionMessage('Existing validation reached');
        $controller->createBienes($request);
    }

    public function test_standalone_index_creation_is_blocked_for_other_clients()
    {
        DB::shouldReceive('connection->getDatabaseName')->once()->andReturn('taxochil_ac-dipreca');
        $request = Mockery::mock(Request::class);
        $request->shouldNotReceive('validate');

        $response = (new DatosActivosController())->createIndiceLista($request);

        $this->assertSame(403, $response->getStatusCode());
    }
}
