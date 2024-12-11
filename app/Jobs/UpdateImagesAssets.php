<?php

namespace App\Jobs;

use App\Models\CrudActivo;
use App\Models\User;
use App\Services\ActivoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class UpdateImagesAssets implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $user;
    private $limit;
    private $finalRows = 0;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($username, int $limit)
    {
        //
        $this->user = User::where('name', '=', $username)->first();

        $this->limit = $limit;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        DB::setDefaultConnection($this->user->conn_field);



        $activoServ = new ActivoService();
        //
        $activos = CrudActivo::whereNull('foto4')->limit($this->limit)->get();

        foreach ($activos as $activo) {
            $activo->foto4 = $activoServ->getUrlAsset($activo, $this->user);
            $activo->save();
        }

        $this->finalRows = $activos->count();
    }


    public function response()
    {
        return $this->finalRows;
    }
}
