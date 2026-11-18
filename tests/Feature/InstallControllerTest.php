<?php

namespace Tests\Feature;

use App\Events\SubscriptionVerified;
use App\Repositories\EnvSaver;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionStatus;
use App\Models\User;

use App\Repositories\SubscriptionManager;
use Carbon\Carbon;
use Dotenv\Dotenv;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * @group tested
 */
class InstallControllerTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_save_env_variables()
    {
        $env = new EnvSaver();

        $data = [
            'APP_INSTALLED' => 'false',
        ];

        $env->saveMany($data);

        $data = [
            'DB_TEST' => 'VALUE'
        ];

        $response = $this->post('/api/install/save', $data);

        $response->assertStatus(200);

        $response->assertJson($data);

        $data = [
            'DB_TEST' => ''
        ];

        $response = $this->post('/api/install/save', $data);

        $response->assertStatus(200);

        $response->assertJson([
            'DB_TEST' => null
        ]);

        $env->saveMany([
            'APP_INSTALLED' => 'true'
        ]);
    }
}
