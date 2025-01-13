<?php

namespace Tests\Feature\Zonas;

use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Route;

class ZonesTest extends TestCase
{
    //use RefreshDatabase;

    public function test_show_zona_returns_a_successful_response(): void
    {
        $token = '4|kkLGOd4q9I5P7ou48rvsXhrEU9iY4lMlR6BYMJqQ';
    
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get(route('zone.show', ['zona' => 33]));
    
        $response->assertStatus(200);

    //php artisan test (general)
    // php artisan test --filter ZonesTest (por clase)
   
}
    
}