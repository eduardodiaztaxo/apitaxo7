<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class MoveToMainDiskJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $connectionName;
    public $projectId;
    public $limit;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($connectionName, $projectId, $limit)
    {
        //
        $this->connectionName = $connectionName;
        $this->projectId = $projectId;
        $this->limit = $limit;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        $code = Artisan::call('command:move-to-main-disk', [
            '--connection' => $this->connectionName,
            '--project_id' => $this->projectId,
            '--limit' => $this->limit,
        ]);

        $output = Artisan::output();

        Log::info('Resultado del comando:', ['code' => $code, 'msg' => $output]);

    }
}
