<?php

namespace App\Console\Commands;

use App\Services\Dump\SQLiteConnService;
use App\Services\Dump\Tables\AddressesDumpService;
use App\Services\Dump\Tables\CrudAssetsDumpService;
use App\Services\Dump\Tables\CyclesDumpService;
use App\Services\Dump\Tables\CyclesCategoriasDumpService;
use App\Services\Dump\Tables\ConteoRegistroDumpService;
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
use App\Services\Dump\Tables\EmplazamientoN3DumpService;
use App\Services\Dump\Tables\EmplazamientoN1DumpService;
use App\Services\Dump\Tables\RegionesDumpService;
use App\Services\Dump\Tables\ComunasDumpService;
use App\Services\Dump\Tables\AtributosDumpService;
use App\Services\Dump\Tables\DifferencesAddressesDumpService;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class ExportInventoryCycleSQLiteDatabase extends Command
{
    /**
     * @var string
     */
    protected $signature = 'command:export-for-offline-inventory-sqlite {--connection=} {--cycle=}';

    /**
     * @var string
     */
    protected $description = 'Export data for offline inventory (Type 1)';

    protected $pdo = null;
    protected $cycle = 0;
    protected $codigo_grupo = '';

    /**
     * @return int
     */
    public function handle()
    {
        $this->info('Starting inventory data export (ID 1)...');

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

        $ciclo = DB::table('inv_ciclos')
            ->where('idCiclo', $this->cycle)
            ->first();

        if (!$ciclo || $ciclo->idTipoCiclo != 1) {
            $this->error('The cycle does not exist or is not an Inventory cycle (ID 1).');
            return 1;
        }

        $grupos = DB::table('inv_ciclos_categorias')
            ->where('idCiclo', $this->cycle)
            ->select('id_grupo')
            ->distinct()
            ->pluck('id_grupo');

        if ($grupos->isEmpty()) {
            $this->error('No groups found associated with the cycle.');
            return 1;
        }

        $this->codigo_grupo = $grupos->implode(',');
        $this->info('Used group code(s): ' . $this->codigo_grupo);

        $timestamp = date('dmY'); // 10032025

        $dbFileName =  $conn_field . '_output_inventory_cycle_' . $this->cycle . '_database.db';
        $zipFileName =  $conn_field . '_output_inventory_cycle_' . $this->cycle . '_' . $timestamp . '.zip';

        $relativeDbPath = 'app/db-dumps/' . $dbFileName;
        $relativeZipPath = 'app/db-dumps/' . $zipFileName;

        $sqlitePath = storage_path($relativeDbPath);

        $pdoServ = new SQLiteConnService($sqlitePath);
        if ($pdoServ->deleteDB()) {
            $this->warn('Old database file deleted.');
        }

        $pdoServ->createSQLiteDatabase();
        $this->pdo = $pdoServ->getCurrentConn();
        $this->info('SQLite DB created successfully.');

        // Common tables
        $this->setCyclesSQLite();
        $this->setAddressByCycle();
        $this->setZonesByCycle();
        $this->setEmplazamientosByCycle();
        $this->setCyclesCategoriasByCycle();
        $this->setConteoRegistroByCycle();
        $this->setAssetsByCycle();
        $this->setInventario();
        $this->setMarca();
        $this->setEstado();
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
        $this->setDiferencias();

        // Specific to Inventory (ID 1)
        $this->setBienesInventario();
        $this->setBieneGrupoFamilia();
        $this->setConfiguracion();
        $this->setMarcaInv();

        $style = new OutputFormatterStyle('white', 'green', array('bold', 'blink'));
        $this->output->getFormatter()->setStyle('success', $style);
        $this->output->writeln('<success>SQLite DB created successfully</success>');

        $zipServ = new \App\Services\Dump\SQLiteZipService($sqlitePath);
        $zipServ->createZipArchive(storage_path($relativeZipPath));

        $version = 1;
        $rows = DB::select('SELECT version FROM db_audits_dumps WHERE cycle_id = ? ', [$this->cycle]);
        if (count($rows) > 0) {
            $version = $rows[0]->version + 1;
        }

        DB::delete('DELETE FROM db_audits_dumps WHERE cycle_id = ? AND `status` = ? ', [$this->cycle, 1]);

        DB::insert('INSERT INTO db_audits_dumps (`status`, `version`, `cycle_id`, `path`, `updated_at`, `created_at`) VALUES (?, ?, ?, ?, ?, ?)', [1, $version, $this->cycle, $relativeZipPath, now(), now()]);

        $this->output->writeln('<success>SQLite DB zipped successfully: ' . $relativeZipPath . '</success>');

        return 0;
    }

    private function setCyclesSQLite() { (new CyclesDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setAddressByCycle() { (new AddressesDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setDiferencias() { (new DifferencesAddressesDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setZonesByCycle() { (new ZonesDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setEmplazamientosByCycle() { (new EmplazamientosN2DumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setAssetsByCycle() { (new CrudAssetsDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setCyclesCategoriasByCycle() { (new CyclesCategoriasDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setConteoRegistroByCycle() { (new ConteoRegistroDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setBienesInventario() { (new BienesInventarioDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setBieneGrupoFamilia() { (new BienGrupoFamiliaDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setCargaTrabajo() { (new CargaTrabajoDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setColores() { (new ColoresDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setCondicionAmbiental() { (new CondicionAmbientalDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setConfiguracion() { (new ConfiguracionDumpService($this->pdo, $this->codigo_grupo, $this->cycle))->runFromController(); }
    private function setEstadoConservacion() { (new EstadoConservacionDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setFamilia() { (new FamiliaDumpService($this->pdo, $this->cycle, $this->codigo_grupo))->runFromController(); }
    private function setForma() { (new FormasDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setGrupo() { (new GruposDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setInventario() { (new InventarioDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setMarca() { (new MarcasDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setMarcaInv() { (new MarcasInventarioDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setMaterial() { (new MaterialDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setOperacional() { (new OperacionalDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setTipoTrabajo() { (new TipoTrabajoDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setEstado() { (new EstadoDumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setEmplazamientoN3() { (new EmplazamientoN3DumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setEmplazamientoN1() { (new EmplazamientoN1DumpService($this->pdo, $this->cycle))->runFromController(); }
    private function setComunas() { (new ComunasDumpService($this->pdo))->runFromController(); }
    private function setRegiones() { (new RegionesDumpService($this->pdo))->runFromController(); }
    private function setAtributos() { (new AtributosDumpService($this->pdo, $this->cycle))->runFromController(); }
}
