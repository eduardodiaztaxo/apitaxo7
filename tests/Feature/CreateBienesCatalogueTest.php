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
                foreach (['descripcion', 'observacion', 'creadoPor', 'fechaCreacion', 'fechaModificacion', 'modo'] as $column) {
                    $table->string($column)->nullable();
                }
                $table->integer('ciclo_inventario')->default(0);
                if ($name === 'indices_listas') {
                    $table->string('listaRapida')->default('0');
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

    public function test_creating_good_in_empty_ecuessence_catalogue_inserts_both_records()
    {
        $response = (new DatosActivosController())->createBienes($this->request());

        $this->assertSame(201, $response->getStatusCode());
        $this->assertDatabaseHas('indices_listas', [
            'idLista' => 1, 'idAtributo' => 1, 'idIndice' => '113',
            'idProyecto' => 9956, 'id_familia' => 113, 'descripcion' => 'SILLA',
        ]);
        $this->assertDatabaseHas('inv_bienes_nuevos', ['descripcion' => 'SILLA', 'id_grupo' => 10]);
    }

    public function test_nuevo_bien_test_with_test_group_and_family_initializes_empty_catalogue()
    {
        // Synthetic selection for the Nuevo Bien screen; screenshot contains no IDs.
        DB::table('dp_grupos')->insert(['id_grupo' => 900, 'descripcion_grupo' => 'TEST']);
        DB::table('dp_familias')->insert([
            'id_familia' => 901, 'id_grupo' => 900, 'descripcion_familia' => 'TEST',
        ]);
        $this->assertSame(0, DB::table('indices_listas')->count());
        $controller = new DatosActivosController();
        $request = $this->request([
            'descripcion' => 'TEST', 'id_grupo' => 900, 'id_familia' => 901,
        ]);

        // Same single request used by the mobile application's save action.
        $response = $controller->createBienes($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertDatabaseHas('indices_listas', [
            'idLista' => 1, 'idAtributo' => 1, 'idIndice' => '901',
            'idProyecto' => 9956, 'id_familia' => 901, 'descripcion' => 'TEST',
        ]);
        $this->assertDatabaseHas('inv_bienes_nuevos', [
            'id_grupo' => 900, 'id_familia' => 901, 'descripcion' => 'TEST',
        ]);

        // A repeated save must not create another catalogue entry.
        $again = $controller->createBienes($request);
        $this->assertSame(201, $again->getStatusCode());
        $this->assertSame(1, DB::table('indices_listas')->count());
        $this->assertSame(1, (int) DB::table('indices_listas')->value('idLista'));
    }

    public function test_same_description_reuses_index_ignoring_case_and_outer_spaces()
    {
        $controller = new DatosActivosController();
        $first = $controller->createIndiceLista($this->request())->getData(true);
        $second = $controller->createIndiceLista($this->request(['descripcion' => ' silla ']));

        $this->assertSame(200, $second->getStatusCode());
        $this->assertFalse($second->getData(true)['created']);
        $this->assertSame($first['data']['idLista'], $second->getData(true)['data']['idLista']);
        $this->assertSame(1, DB::table('indices_listas')->count());
    }

    public function test_different_description_in_same_family_gets_next_id()
    {
        $controller = new DatosActivosController();
        $controller->createIndiceLista($this->request());
        $response = $controller->createIndiceLista($this->request(['descripcion' => 'MESA']));

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(2, $response->getData(true)['data']['idLista']);
        $this->assertSame(2, DB::table('indices_listas')->count());
    }

    public function test_wrong_group_prevents_both_inserts()
    {
        DB::table('dp_grupos')->insert(['id_grupo' => 20]);
        $response = (new DatosActivosController())->createBienes($this->request(['id_grupo' => 20]));

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(0, DB::table('indices_listas')->count());
        $this->assertSame(0, DB::table('inv_bienes_nuevos')->count());
    }

    public function test_other_client_keeps_validation_and_does_not_initialize_empty_catalogue()
    {
        // Change the logical name only; preserve the same isolated in-memory PDO.
        DB::connection()->setDatabaseName('taxochil_ac-ec-jugos');
        try {
            (new DatosActivosController())->createBienes($this->request());
            $this->fail('Expected existing attribute validation to reject the empty catalogue');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('idAtributo', $exception->errors());
        }
        $this->assertSame(0, DB::table('indices_listas')->count());
        $this->assertSame(0, DB::table('inv_bienes_nuevos')->count());
    }

    public function test_other_client_with_catalogue_can_still_create_good_without_new_index()
    {
        $controller = new DatosActivosController();
        $controller->createIndiceLista($this->request());
        DB::connection()->setDatabaseName('taxochil_ac-ec-jugos');

        $response = $controller->createBienes($this->request(['descripcion' => 'OTRO BIEN']));

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(1, DB::table('indices_listas')->count());
        $this->assertDatabaseHas('inv_bienes_nuevos', ['descripcion' => 'OTRO BIEN']);
    }

    /** @dataProvider existingMaximums */
    public function test_next_index_uses_its_own_auto_increment($catalogueMax, $goodsMax, $expected)
    {
        $controller = new DatosActivosController();
        $controller->createBienes($this->request());
        DB::table('indices_listas')->update(['idLista' => $catalogueMax]);
        DB::table('inv_bienes_nuevos')->update(['idLista' => $goodsMax]);

        $response = $controller->createIndiceLista($this->request(['descripcion' => 'TEST NUEVO']));

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame($expected, $response->getData(true)['data']['idLista']);
    }

    public static function existingMaximums(): array
    {
        return [
            'catalogue has largest ID' => [4836, 10, 4837],
            'goods IDs do not control catalogue counter' => [10, 5000, 11],
        ];
    }

    public function test_same_name_in_another_family_creates_separate_index()
    {
        $controller = new DatosActivosController();
        $controller->createIndiceLista($this->request());
        DB::table('dp_familias')->insert(['id_familia' => 114, 'id_grupo' => 10]);

        $response = $controller->createIndiceLista($this->request(['id_familia' => 114]));

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(2, DB::table('indices_listas')->count());
        $this->assertDatabaseHas('indices_listas', ['idIndice' => '114', 'id_familia' => 114, 'descripcion' => 'SILLA']);
    }

    public function test_same_name_in_another_project_does_not_reuse_first_projects_index()
    {
        $controller = new DatosActivosController();
        $controller->createIndiceLista($this->request());
        DB::table('sec_user_proyectos')->update(['idProyecto' => 9957]);
        DB::table('dp_grupos')->update(['id_proyecto' => 9957]);
        DB::table('dp_familias')->update(['id_proyecto' => 9957]);

        // Incoming project cannot override the authenticated user's project.
        $response = $controller->createIndiceLista($this->request(['idProyecto' => 9956]));

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame(9957, $response->getData(true)['data']['idProyecto']);
        $this->assertSame(2, DB::table('indices_listas')->count());
    }

    public function test_missing_project_prevents_both_inserts()
    {
        DB::table('sec_user_proyectos')->delete();

        $response = (new DatosActivosController())->createBienes($this->request());

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(0, DB::table('indices_listas')->count());
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

    public static function invalidGoodInputs(): array
    {
        return [
            'unknown family' => [['id_familia' => 999999], 'id_familia'],
            'unknown group' => [['id_grupo' => 999999], 'id_grupo'],
            'unknown cycle' => [['ciclo_inventario' => 999999], 'ciclo_inventario'],
            'negative attribute' => [['idAtributo' => -1], 'idAtributo'],
            'missing attribute' => [['idAtributo' => null], 'idAtributo'],
            'empty description' => [['descripcion' => ''], 'descripcion'],
            'description exceeds column size' => [['descripcion' => str_repeat('A', 101)], 'descripcion'],
            'observation exceeds column size' => [['observacion' => str_repeat('A', 101)], 'observacion'],
        ];
    }

    /** @dataProvider ecuessenceFamilies */
    public function test_ecuessence_project_9944_real_group_family_pairs($group, $family, $description)
    {
        DB::table('sec_user_proyectos')->update(['idProyecto' => 9944]);
        DB::table('dp_grupos')->insert(['id_grupo' => $group, 'id_proyecto' => 9944]);
        DB::table('dp_familias')->insert([
            'id_familia' => $family, 'id_grupo' => $group,
            'id_proyecto' => 9944, 'descripcion_familia' => $description,
        ]);

        $response = (new DatosActivosController())->createBienes($this->request([
            'id_grupo' => $group, 'id_familia' => $family, 'descripcion' => 'TEST',
        ]));

        $this->assertSame(201, $response->getStatusCode());
        $this->assertDatabaseHas('indices_listas', [
            'idLista' => 1, 'idAtributo' => 1, 'idIndice' => (string) $family,
            'idProyecto' => 9944, 'id_familia' => $family, 'descripcion' => 'TEST',
        ]);
        $this->assertDatabaseHas('inv_bienes_nuevos', [
            'id_grupo' => $group, 'id_familia' => $family, 'idProyecto' => 9944,
        ]);
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
