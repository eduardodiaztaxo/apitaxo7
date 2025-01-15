<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UpdateUserPassJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $user;
    private $password;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($username, $password)
    {
        //
        $this->user = User::where('name', '=', $username)->first();

        $this->password = $password;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
        DB::setDefaultConnection($this->user->conn_field);

        $this->user->forceFill([
            'password' => Hash::make($this->password),
            'remember_token' => Str::random(60),
        ])->save();
    }
}
