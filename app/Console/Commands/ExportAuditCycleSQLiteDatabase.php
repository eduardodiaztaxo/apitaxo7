<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\V1\CiclosController;
use App\Http\Controllers\Api\V1\CiclosUbicacionesController;
use App\Http\Controllers\Api\V1\ZonaEmplazamientosController;
use App\Services\Dump\SQLiteConnService;
use App\Services\Dump\Tables\AddressesDumpService;
use App\Services\Dump\Tables\CrudAssetsDumpService;
use App\Services\Dump\Tables\CyclesDumpService;
use App\Services\Dump\Tables\CyclesCategoriasDumpService;
use App\Services\Dump\Tables\CyclesPuntoDumpService;
use App\Services\Dump\Tables\ConteoRegistroDumpService;
use App\Services\Dump\Tables\SubZonesDumpService;
use App\Services\Dump\Tables\EmplazamientosN2DumpService;
use App\Services\Dump\Tables\ZonesDumpService;
use App\Services\Dump\Tables\BienesInventarioDumpService;
use App\Services\Dump\Tables\BienGrupoFamiliaDumpService;
use App\Services\Dump\Tables\CargaTrabajoDumpService;
use App\Services\Dump\Tables\ColoresDumpService;
use App\Services\Dump\Tables\CondicionAmbientalDumpService;
use App\Services\Dump\Tables\ConfiguracionDumpService;
use App\Services\Dump\Tables\EstadoConservacionDumpService;
use App\Services\Dump\Tables\FamiliaDumpService;
use App\Services\Dump\Tables\FormasDumpService;
use App\Services\Dump\Tables\GruposDumpService;
use App\Services\Dump\Tables\InventarioDumpService;
use App\Services\Dump\Tables\MarcasDumpService;
use App\Services\Dump\Tables\MarcasInventarioDumpService;
use App\Services\Dump\Tables\MaterialDumpService;
use App\Services\Dump\Tables\OperacionalDumpService;
use App\Services\Dump\Tables\TipoTrabajoDumpService;
use App\Services\Dump\Tables\EstadoDumpService;
use App\Services\Dump\Tables\ResponsableDumpService;
use App\Services\Dump\Tables\EmplazamientoN3DumpService;
use App\Services\Dump\Tables\EmplazamientoN1DumpService;
use App\Services\Dump\Tables\RegionesDumpService;
use App\Services\Dump\Tables\ComunasDumpService;
use App\Services\Dump\Tables\AtributosDumpService;

use Illuminate\Console\Command;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class ExportAuditCycleSQLiteDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:export-for-offline-auditing-sqlite {--connection=} {--cycle=}';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected $pdo = null;

    protected $cycle = 0;

    protected $codigo_grupo = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $this->info('Starting data export...');

        $conn_field = $this->option('connection');

        if (!$conn_field) {
            $this->error('--connection option is required.');
            return 1;
        }

        $this->cycle = $this->option('cycle');

        if (!$this->cycle) {
            $this->error('--cycle option is required.');
            return 1;
        }

        DB::setDefaultConnection($conn_field);

        $tipoCiclo = DB::table('inv_ciclos')
            ->where('idCiclo', $this->cycle)
            ->value('idTipoCiclo');

        if (!$tipoCiclo) {
            $this->error('No se encontró el ciclo con el ID especificado.');
            return 1;
        }

        if ($tipoCiclo == 1) {
            $grupos = DB::table('inv_ciclos_categorias')
                ->where('idCiclo', $this->cycle)
                ->select('id_grupo')
                ->distinct()
                ->pluck('id_grupo');

            if ($grupos->isEmpty()) {
                $this->error('No se encontraron grupos asociados al ciclo.');
                return 1;
            }

            $this->codigo_grupo = $grupos->implode(',');

            $this->info('Código grupo(s) usado(s): ' . $this->codigo_grupo);
        }

        $relativePath = 'app/db-dumps/' . $conn_field . '/output_audit_cycle_' . $this->cycle . '_database.db';
        $relativeZipPath = str_replace('.db', '.zip', $relativePath);
        $sqlitePath = storage_path($relativePath);

        $pdoServ = new SQLiteConnService($sqlitePath);

        if ($pdoServ->deleteDB()) {
            $this->warn('output_database.db file deleted.');
        }

        $pdoServ->createSQLiteDatabase();
        $this->pdo = $pdoServ->getCurrentConn();
        $this->info('SQLite DB created successfully.');


        $this->setCyclesSQLite();
        $this->setAddressByCycle();
        $this->setZonesByCycle();
        $this->setEmplazamientosByCycle();
        $this->setCyclesCategoriasByCycle();
        $this->setConteoRegistroByCycle();
        // $this->setSubZonesByCycle();
        $this->setAssetsByCycle();
        // $this->setResponsable();
        $this->setInventario();
        $this->setMarca();
        $this->setEstado();
        $this->setGrupo();
        $this->setFamilia();
        $this->setRegiones();
        $this->setComunas();
        $this->setCargaTrabajo();
        $this->setColores();
        $this->setCondicionAmbiental();
        $this->setEstadoConservacion();
        $this->setFamilia();
        $this->setForma();
        $this->setGrupo();
        $this->setMaterial();
        $this->setOperacional();
        $this->setTipoTrabajo();
        $this->setEmplazamientoN3();
        $this->setEmplazamientoN1();
        $this->setAtributos();

        if ($tipoCiclo == 1) {
            $this->setBienesInventario();
            $this->setBieneGrupoFamilia();
            $this->setConfiguracion();
            $this->setMarcaInv();
        }
        // $this->setCyclesPuntosByCycle();


        $style = new OutputFormatterStyle('white', 'green', array('bold', 'blink'));
        $this->output->getFormatter()->setStyle('success', $style);
        $this->output->writeln('<success>SQLite DB created successfully</success>');

        $zipServ = new \App\Services\Dump\SQLiteZipService($sqlitePath);
        $zipServ->createZipArchive();

        $version = 1;

        $rows = DB::select('SELECT version FROM db_audits_dumps 
        WHERE cycle_id = ? ', [$this->cycle]);

        if (count($rows) > 0) {
            $version = $rows[0]->version + 1;
        }

        DB::delete('DELETE FROM db_audits_dumps 
        WHERE cycle_id = ? AND `status` = ? ', [$this->cycle, 1]);

        DB::insert('INSERT INTO db_audits_dumps (
            `status`, 
            `version`,
            `cycle_id`, 
            `path`, 
            `updated_at`, 
            `created_at`) VALUES (?, ?, ?, ?, ?, ?)', [1, $version, $this->cycle, $relativeZipPath, now(), now()]);

        $this->output->writeln('<success>SQLite DB zipped successfully: ' . $relativeZipPath . '</success>');

        return 0;
    }

    private function setCyclesSQLite()
    {
        (new CyclesDumpService(
            $this->pdo,
            $this->cycle
        ))->runFromController();

        $this->info('Cycles inserted in the SQLite DB.');
    }

    private function setAddressByCycle()
    {

        (new AddressesDumpService(
            $this->pdo,
            $this->cycle
        ))->runFromController();


        $this->info('Addresses inserted in SQLite DB.');
    }

    private function setZonesByCycle()
    {

        (new ZonesDumpService(
            $this->pdo,
            $this->cycle
        ))->runFromController();

        $this->info('Zones inserted in SQLite DB.');
    }

    private function setEmplazamientosByCycle()
    {
        (new EmplazamientosN2DumpService(
            $this->pdo,
            $this->cycle
        ))->runFromController();

        $this->info('Emplazamientos inserted in SQLite DB.');
    }

    private function setAssetsByCycle()
    {
        (new CrudAssetsDumpService(
            $this->pdo,
            $this->cycle
        ))->runFromController();

        $this->info('Assets inserted in SQLite DB.');
    }

    private function setCyclesCategoriasByCycle()
    {
        (new CyclesCategoriasDumpService(
            $this->pdo,
            $this->cycle
        ))->runFromController();

        $this->info('Categorías de ciclos insertadas en SQLite DB.');
    }

    private function setConteoRegistroByCycle()
    {
        (new ConteoRegistroDumpService(
            $this->pdo,
            $this->cycle
        ))->runFromController();

        $this->info('Conteo de registros insertado en SQLite DB.');
    }

    private function setSubZonesByCycle()
    {
        (new SubZonesDumpService(
            $this->pdo,
            $this->cycle
        ))->runFromController();

        $this->info('Subzonas insertadas en SQLite DB.');
    }

    private function setBienesInventario()
    {
        (new BienesInventarioDumpService(
            $this->pdo,
            $this->cycle
        ))->runFromController();

        $this->info('bienes insertadas en SQLite DB.');
    }

    private function setBieneGrupoFamilia()
    {
        (new BienGrupoFamiliaDumpService(
            $this->pdo,
            $this->cycle
        ))->runFromController();

        $this->info('bgf insertadas en SQLite DB.');
    }

    private function setCargaTrabajo()
    {
        (new CargaTrabajoDumpService(
            $this->pdo
        ))->runFromController();

        $this->info('carga insertadas en SQLite DB.');
    }

    private function setColores()
    {
        (new ColoresDumpService(
            $this->pdo
        ))->runFromController();

        $this->info('colores insertadas en SQLite DB.');
    }

    private function setCondicionAmbiental()
    {
        (new CondicionAmbientalDumpService(
            $this->pdo
        ))->runFromController();

        $this->info('condicion ambiental insertadas en SQLite DB.');
    }

    private function setConfiguracion()
    {
        (new ConfiguracionDumpService(
            $this->pdo,
            $this->codigo_grupo
        ))->runFromController();

        $this->info('configuracion insertadas en SQLite DB.');
    }
    private function setEstadoConservacion()
    {
        (new EstadoConservacionDumpService(
            $this->pdo
        ))->runFromController();

        $this->info('estado conservacion insertadas en SQLite DB.');
    }

    private function setFamilia()
    {
        (new FamiliaDumpService(
            $this->pdo,
            $this->cycle,
            $this->codigo_grupo
        ))->runFromController();

        $this->info('familia insertadas en SQLite DB.');
    }
    private function setForma()
    {
        (new FormasDumpService(
            $this->pdo
        ))->runFromController();

        $this->info('forma insertadas en SQLite DB.');
    }

    private function setGrupo()
    {
        (new GruposDumpService(
            $this->pdo,
            $this->cycle
        ))->runFromController();

        $this->info('grupo insertadas en SQLite DB.');
    }
    private function setInventario()
    {
        (new InventarioDumpService(
            $this->pdo,
            $this->cycle
        ))->runFromController();

        $this->info('inventario insertadas en SQLite DB.');
    }

    private function setMarca()
    {
        (new MarcasDumpService(
            $this->pdo,
            $this->cycle
        ))->runFromController();

        $this->info('marca insertadas en SQLite DB.');
    }
    private function setMarcaInv()
    {
        (new MarcasInventarioDumpService(
            $this->pdo,
            $this->cycle
        ))->runFromController();

        $this->info('marcaInv insertadas en SQLite DB.');
    }

    private function setMaterial()
    {
        (new MaterialDumpService(
            $this->pdo
        ))->runFromController();

        $this->info('material insertadas en SQLite DB.');
    }
    private function setOperacional()
    {
        (new OperacionalDumpService(
            $this->pdo
        ))->runFromController();

        $this->info('operacional insertadas en SQLite DB.');
    }

    private function setTipoTrabajo()
    {
        (new TipoTrabajoDumpService(
            $this->pdo
        ))->runFromController();

        $this->info('tipotrabajo insertadas en SQLite DB.');
    }

    private function setEstado()
    {
        (new EstadoDumpService(
            $this->pdo
        ))->runFromController();

        $this->info('Estados insertadas en SQLite DB.');
    }

    private function setResponsable()
    {

        (new ResponsableDumpService(
            $this->pdo
        ))->runFromController();


        $this->info('Responsables inserted in SQLite DB.');
    }

     private function setEmplazamientoN3()
    {

        (new EmplazamientoN3DumpService(
            $this->pdo,
            $this->cycle
        ))->runFromController();


        $this->info('EmpN3 inserted in SQLite DB.');
    }

       private function setEmplazamientoN1()
    {

        (new EmplazamientoN1DumpService(
            $this->pdo,
            $this->cycle
        ))->runFromController();


        $this->info('EmpN1 inserted in SQLite DB.');
    }
    private function setComunas()
    {

        (new ComunasDumpService(
            $this->pdo
        ))->runFromController();


        $this->info('comunas inserted in SQLite DB.');
    }

      private function setRegiones()
    {

        (new RegionesDumpService(
            $this->pdo
        ))->runFromController();


        $this->info('Regiones inserted in SQLite DB.');
    }

     private function setAtributos()
    {

        (new AtributosDumpService(
            $this->pdo
        ))->runFromController();


        $this->info('atributos inserted in SQLite DB.');
    }
    // private function setCyclesPuntosByCycle()
    // {
    //     (new CyclesPuntoDumpService(
    //         $this->pdo,
    //         $this->cycle
    //     ))->runFromController();

    //     $this->info('ciclos_punto insertados en SQLite DB.');
    // }
}
