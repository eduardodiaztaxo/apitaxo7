<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V1\Comunes\DatosActivosController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateBienesCatalogueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Only the logical database name is Ecuessence. All SQL goes to RAM.
        $connection = new SQLiteConnection(new \PDO('sqlite::memory:'), 'taxochil_ac-ecuessence');
        config(['database.connections.catalogue_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::extend('catalogue_test', function () use ($connection) {
            return $connection;
        });
        DB::setDefaultConnection('catalogue_test');
        Auth::shouldReceive('user')->andReturn((object) ['name' => 'catalogue-tester']);

        $schema = $connection->getSchemaBuilder();
        $schema->create('dp_grupos', function (Blueprint $table) {
            $table->integer('id_grupo');
            $table->string('descripcion_grupo')->nullable();
            $table->integer('id_proyecto')->default(9956);
            $table->string('soft_delete')->default('N');
        });
        $schema->create('dp_familias', function (Blueprint $table) {
            $table->integer('id_familia');
            $table->integer('id_grupo');
            $table->string('descripcion_familia')->nullable();
            $table->integer('id_proyecto')->default(9956);
            $table->string('soft_delete')->default('N');
        });
        $schema->create('inv_ciclos', function (Blueprint $table) {
            $table->integer('idCiclo');
            $table->integer('id_proyecto')->default(9956);
        });
        $schema->create('sec_user_proyectos', function (Blueprint $table) {
            $table->string('login');
            $table->integer('idProyecto');
        });
        foreach (['indices_listas', 'inv_bienes_nuevos'] as $name) {
            $schema->create($name, function (Blueprint $table) use ($name) {
                if ($name === 'indices_listas') {
                    $table->increments('idLista');
                } else {
                    $table->integer('idLista');
                }
                $table->integer('idAtributo');
                $table->string('idIndice');
                $table->integer('idProyecto');
                $table->integer('id_familia');
                foreach (['descripcion', 'observacion', 'creadoPor', 'fechaCreacion', 'fechaModificacion', 'modo', 'modificadoPor', 'estado', 'foto', 'listaRapida'] as $column) {
                    $table->string($column)->nullable();
                }
                $table->integer('ciclo_inventario')->default(0);
                if ($name === 'indices_listas') {
                    $table->integer('en_listas')->default(0);
                    $table->string('creado_por')->nullable();
                    $table->string('creado_el')->nullable();
                    $table->integer('fl_catalogo_activo')->default(1);
                    $table->integer('fl_creado_auto')->default(0);
                } else {
                    $table->integer('id_grupo');
                }
                if ($name !== 'indices_listas') {
                    $table->primary(['idLista', 'idAtributo', 'idIndice', 'idProyecto']);
                }
            });
        }

        $connection->getPdo()->sqliteCreateFunction('CONCAT', function (...$parts) { return implode('', $parts); });
        $schema->create('inv_ciclos_categorias', function (Blueprint $table) {
            $table->integer('idCiclo');
            $table->integer('id_proyecto');
            $table->integer('id_grupo');
            $table->integer('id_familia');
        });
        DB::table('inv_ciclos_categorias')->insert(['idCiclo' => 25, 'id_proyecto' => 9956, 'id_grupo' => 10, 'id_familia' => 113]);

        DB::table('dp_grupos')->insert(['id_grupo' => 10]);
        DB::table('dp_familias')->insert(['id_familia' => 113, 'id_grupo' => 10]);
        DB::table('inv_ciclos')->insert(['idCiclo' => 25]);
        DB::table('sec_user_proyectos')->insert(['login' => 'catalogue-tester', 'idProyecto' => 9956]);
    }

    private function request(array $overrides = []): Request
    {
        return Request::create('/api/v1/create-bienes', 'POST', array_merge([
            'descripcion' => 'SILLA',
            'idAtributo' => 1,
            'id_familia' => 113,
            'id_grupo' => 10,
            'ciclo_inventario' => 25,
        ], $overrides));
    }

    public function test_empty_catalogue_creates_only_new_good_and_list_returns_it_once()
    {
        $response = (new DatosActivosController())->createBienes($this->request());
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(1, $response->getData(true)['idLista']);
        $this->assertSame(0, DB::table('indices_listas')->count());
        $this->assertSame(1, DB::table('inv_bienes_nuevos')->count());
        $this->assertDatabaseHas('inv_bienes_nuevos', [
            'idLista' => 1, 'idAtributo' => 1, 'idIndice' => '113',
            'idProyecto' => 9956, 'id_grupo' => 10, 'id_familia' => 113, 'descripcion' => 'SILLA',
        ]);
        $cycle = \App\Models\InvCiclo::find(25);
        $options = $cycle->bienesGrupoFamiliaByCycle()->where('descripcion', 'SILLA')->get();
        $this->assertCount(1, $options);
        $this->assertSame(1, (int) $options->first()->idLista);
    }

    public function test_repeated_name_reuses_new_good_without_inserting_anything()
    {
        $controller = new DatosActivosController();
        $first = $controller->createBienes($this->request());
        $again = $controller->createBienes($this->request(['descripcion' => ' silla ']));
        $this->assertSame(200, $again->getStatusCode());
        $this->assertSame($first->getData(true)['idLista'], $again->getData(true)['idLista']);
        $this->assertSame(0, DB::table('indices_listas')->count());
        $this->assertSame(1, DB::table('inv_bienes_nuevos')->count());
    }

    private function seedBase($id = 100, $description = 'SILLA')
    {
        DB::table('indices_listas')->insert([
            'idLista' => $id, 'idAtributo' => 1, 'idIndice' => '113', 'idProyecto' => 9956,
            'id_familia' => 113, 'descripcion' => $description,
        ]);
    }

    public function test_existing_base_entry_is_read_only_and_not_copied()
    {
        $this->seedBase();
        $response = (new DatosActivosController())->createBienes($this->request());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(100, (int) $response->getData(true)['idLista']);
        $this->assertSame(10, $response->getData(true)['id_grupo']);
        $this->assertSame(1, DB::table('indices_listas')->count());
        $this->assertSame(0, DB::table('inv_bienes_nuevos')->count());
    }

    public function test_new_name_allocates_after_both_catalogues_without_id_collision()
    {
        $this->seedBase(100, 'BASE');
        $controller = new DatosActivosController();
        $first = $controller->createBienes($this->request());
        $second = $controller->createBienes($this->request(['descripcion' => 'MESA']));
        $this->assertSame(101, $first->getData(true)['idLista']);
        $this->assertSame(102, $second->getData(true)['idLista']);
        $this->assertSame(1, DB::table('indices_listas')->count());
        $this->assertSame(2, DB::table('inv_bienes_nuevos')->count());
    }

    public function test_same_name_in_other_family_is_another_good()
    {
        $controller = new DatosActivosController();
        $controller->createBienes($this->request());
        DB::table('dp_familias')->insert(['id_familia' => 114, 'id_grupo' => 10]);
        $response = $controller->createBienes($this->request(['id_familia' => 114]));
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(2, DB::table('inv_bienes_nuevos')->count());
        $this->assertSame(0, DB::table('indices_listas')->count());
    }

    public function test_project_is_taken_from_user_and_existing_other_project_is_not_reused()
    {
        $controller = new DatosActivosController();
        $controller->createBienes($this->request());
        DB::table('sec_user_proyectos')->update(['idProyecto' => 9957]);
        DB::table('dp_grupos')->update(['id_proyecto' => 9957]);
        DB::table('dp_familias')->update(['id_proyecto' => 9957]);
        DB::table('inv_ciclos')->update(['id_proyecto' => 9957]);
        $response = $controller->createBienes($this->request(['idProyecto' => 9956]));
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(9957, $response->getData(true)['idProyecto']);
        $this->assertSame(2, DB::table('inv_bienes_nuevos')->count());
    }

    public function test_wrong_group_prevents_creation()
    {
        DB::table('dp_grupos')->insert(['id_grupo' => 20]);
        $response = (new DatosActivosController())->createBienes($this->request(['id_grupo' => 20]));
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(0, DB::table('inv_bienes_nuevos')->count());
    }

    public function test_missing_project_prevents_creation()
    {
        DB::table('sec_user_proyectos')->delete();
        $response = (new DatosActivosController())->createBienes($this->request());
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(0, DB::table('inv_bienes_nuevos')->count());
    }

    public function test_foreign_cycle_prevents_creation()
    {
        DB::table('inv_ciclos')->update(['id_proyecto' => 9999]);
        $response = (new DatosActivosController())->createBienes($this->request());
        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(0, DB::table('inv_bienes_nuevos')->count());
    }

    public function test_other_client_keeps_original_attribute_validation()
    {
        DB::connection()->setDatabaseName('taxochil_ac-ec-jugos');
        try {
            (new DatosActivosController())->createBienes($this->request());
            $this->fail('Expected original attribute validation');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('idAtributo', $exception->errors());
        }
        $this->assertSame(0, DB::table('inv_bienes_nuevos')->count());
    }

    public function test_other_client_can_create_with_preloaded_attribute()
    {
        $this->seedBase();
        DB::connection()->setDatabaseName('taxochil_ac-ec-jugos');
        $response = (new DatosActivosController())->createBienes($this->request(['descripcion' => 'OTRO BIEN']));
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(1, DB::table('indices_listas')->count());
        $this->assertDatabaseHas('inv_bienes_nuevos', ['descripcion' => 'OTRO BIEN']);
    }

    public function test_smallint_limit_is_rejected_before_insert()
    {
        $this->seedBase(32767, 'BASE');
        $response = (new DatosActivosController())->createBienes($this->request());
        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(0, DB::table('inv_bienes_nuevos')->count());
    }

    /** @dataProvider invalidGoodInputs */
    public function test_invalid_data_is_rejected_before_creating_any_records(array $input, $field)
    {
        try {
            (new DatosActivosController())->createBienes($this->request($input));
            $this->fail('Expected validation failure');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
        }
        $this->assertSame(0, DB::table('indices_listas')->count());
        $this->assertSame(0, DB::table('inv_bienes_nuevos')->count());
    }

    /** @dataProvider ecuessenceFamilies */
    public function test_real_ecuessence_groups_create_only_new_goods($group, $family, $description)
    {
        DB::table('sec_user_proyectos')->update(['idProyecto' => 9944]);
        DB::table('inv_ciclos')->update(['id_proyecto' => 9944]);
        DB::table('dp_grupos')->insert(['id_grupo' => $group, 'id_proyecto' => 9944]);
        DB::table('dp_familias')->insert([
            'id_familia' => $family, 'id_grupo' => $group, 'id_proyecto' => 9944,
            'descripcion_familia' => $description,
        ]);
        $response = (new DatosActivosController())->createBienes($this->request([
            'id_grupo' => $group, 'id_familia' => $family, 'descripcion' => 'TEST',
        ]));
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(0, DB::table('indices_listas')->count());
        $this->assertDatabaseHas('inv_bienes_nuevos', [
            'idLista' => 1, 'idAtributo' => 1, 'idIndice' => (string) $family,
            'id_grupo' => $group, 'id_familia' => $family, 'idProyecto' => 9944,
        ]);
    }

    public static function invalidGoodInputs(): array
    {
        return [
            'unknown family' => [['id_familia' => 999999], 'id_familia'],
            'unknown group' => [['id_grupo' => 999999], 'id_grupo'],
            'unknown cycle' => [['ciclo_inventario' => 999999], 'ciclo_inventario'],
            'unsupported attribute' => [['idAtributo' => 2], 'idAtributo'],
            'negative attribute' => [['idAtributo' => -1], 'idAtributo'],
            'missing attribute' => [['idAtributo' => null], 'idAtributo'],
            'empty description' => [['descripcion' => ''], 'descripcion'],
            'description exceeds column size' => [['descripcion' => str_repeat('A', 101)], 'descripcion'],
            'observation exceeds column size' => [['observacion' => str_repeat('A', 101)], 'observacion'],
        ];
    }

    public static function ecuessenceFamilies(): array
    {
        return [
            'computo' => [34, 211, 'EQUIPO DE COMPUTO'],
            'oficina' => [33, 212, 'EQUIPOS DE OFICINA'],
            'herramientas' => [35, 213, 'HERRAMIENTAS'],
            'maquinaria' => [32, 214, 'MAQUINARIA Y EQUIPO'],
        ];
    }

    /** @dataProvider unavailableCatalogues */
    public function test_unavailable_group_or_family_prevents_both_inserts($table, array $changes)
    {
        DB::table($table)->update($changes);

        $response = (new DatosActivosController())->createBienes($this->request());

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(0, DB::table('indices_listas')->count());
        $this->assertSame(0, DB::table('inv_bienes_nuevos')->count());
    }

    public static function unavailableCatalogues(): array
    {
        return [
            'group belongs to another project' => ['dp_grupos', ['id_proyecto' => 9944]],
            'family belongs to another project' => ['dp_familias', ['id_proyecto' => 9944]],
            'group disabled' => ['dp_grupos', ['soft_delete' => 'S']],
            'family disabled' => ['dp_familias', ['soft_delete' => 'S']],
        ];
    }
}
