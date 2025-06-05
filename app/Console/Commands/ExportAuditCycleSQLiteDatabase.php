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
use App\Services\Dump\Tables\ConteoRegistroDumpService;
use App\Services\Dump\Tables\SubZonesDumpService;
use App\Services\Dump\Tables\EmplazamientosDumpService;
use App\Services\Dump\Tables\ZonesDumpService;
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

        $relativePath = 'app/db-dumps/' . $conn_field . '/output_audit_cycle_' . $this->cycle . '_database.db';

        $relativeZipPath = str_replace('.db', '.zip', $relativePath);


        $sqlitePath = storage_path($relativePath);

        $pdoServ = new SQLiteConnService(
            $sqlitePath
        );

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
        $this->setAssetsByCycle();

        $this->setCyclesCategoriasByCycle();
        $this->setConteoRegistroByCycle();
        $this->setSubZonesByCycle();


        $style = new OutputFormatterStyle('white', 'green', array('bold', 'blink'));

        $this->output->getFormatter()->setStyle('success', $style);

        $this->output->writeln('<success>SQLite DB created successfully</success>');



        $zipServ = new \App\Services\Dump\SQLiteZipService($sqlitePath);
        $zipServ->createZipArchive();


        DB::delete('DELETE FROM db_audits_dumps 
        WHERE cycle_id = ? AND `status` = ? ', [$this->cycle, 1]);

        DB::insert('INSERT INTO db_audits_dumps (
            `status`, 
            `cycle_id`, 
            `path`, 
            `updated_at`, 
            `created_at`) VALUES (?, ?, ?, ?, ?)', [1, $this->cycle, $relativeZipPath, now(), now()]);

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
        (new EmplazamientosDumpService(
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
}
