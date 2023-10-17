<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Base\OldUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //\App\Models\User::factory(10)->create();


        OldUser::all()->each(function($old) { 
            $user                       = new User(); 
            $user->name                 = $old->user_login;
            $user->email                = $old->user_login;
            $user->email_verified_at    = now();
            $user->password             = Hash::make($old->user_pw);
            $user->remember_token       = Str::random(10);

            $domain                     = explode('@', $old->user_login);
            $dom                        = isset($domain[1]) ? $domain[1] : ''; 

            switch($dom){
                case 'esmax.cl':
                    $user->conn_field   = 'mysql_esmax';
                    break;
                case 'junji.cl':
                    $user->conn_field   = 'mysql_junji';
                    break;

            }

            $user->save();
        });


        \App\Models\Post::factory(120)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
