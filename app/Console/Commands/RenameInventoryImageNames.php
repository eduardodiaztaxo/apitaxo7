<?php

namespace App\Console\Commands;

use App\Services\ImageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RenameInventoryImageNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:normalize-update-picture-names-inventory {--connection=} {--project_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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


        $result = DB::select('SELECT project_name FROM api_project_conn WHERE conn_field = ?', [$conn_field]);

        if (empty($result)) {
            $this->error('No project found for the given connection field.');
            return 1;
        }

        $customer_name = $result[0]->project_name;

        $project_id = $this->option('project_id');

        if (!$project_id) {
            $this->error('--project_id option is required.');
            return 1;
        }

        DB::setDefaultConnection($conn_field);


        $rows_updated = ImageService::updateFileNamesWhenEtiquetaFieldNoMatchWithImageName($project_id, $customer_name);

        $this->info("Total image names updated: " . $rows_updated);
    }
}
