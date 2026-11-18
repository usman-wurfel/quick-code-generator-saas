<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\SubscriptionPlan;

class SubscriptionPlanSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        SubscriptionPlan::factory()->trial()->create();
        SubscriptionPlan::factory()->starter()->create();
        SubscriptionPlan::factory()->lite()->create();
        SubscriptionPlan::factory()->pro()->create();
    }
}
