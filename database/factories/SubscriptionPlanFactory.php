<?php

namespace Database\Factories;

use App\Models\QRCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [];
    }

    public function trial()
    {
        return $this->state([
            'name' => 'TRIAL',
            'price' => 0,
            'is_popular' => false,
            'number_of_dynamic_qrcodes' => 2,
            'number_of_scans' => 50,
            'number_of_custom_domains' => 1,
            'is_hidden' => true,
            'is_trial' => true,
            'trial_days' => 15,
            'qr_types' => QRCode::getTypes(),
            'features' => [
                'shape.none',
                'shape.circle',
                'shape.cloud'
            ],
        ]);
    }

    public function starter()
    {
        return $this->state(function ($attributes) {
            return [
                'name' => 'STARTER',
                'monthly_price' => 1,
                'is_popular' => false,
                'number_of_dynamic_qrcodes' => 10,
                'number_of_scans' => 10000,
                'number_of_custom_domains' => 1,
                'qr_types' => QRCode::getTypes(),
                'features' => [
                    'qrcode.copy',
                    'shape.none',
                    'shape.circle',
                    'shape.cloud',
                    'shape.shopping-cart'
                ],

            ];
        });
    }

    public function lite()
    {
        return $this->state(function ($attributes) {
            return [
                'name' => 'LITE',
                'monthly_price' => 1.5,
                'is_popular' => true,
                'number_of_dynamic_qrcodes' => 15,
                'number_of_custom_domains' => 2,
                'number_of_scans' => 15000,
                'qr_types' => QRCode::getTypes(),
                'features' => [
                    'qrcode.copy',
                    'shape.none',
                    'shape.circle',
                    'shape.cloud',
                    'shape.shopping-cart',
                    'shape.gift'
                ],
            ];
        });
    }

    public function pro()
    {
        return $this->state(function ($attributes) {
            return [
                'name' => 'PRO',
                'monthly_price' => 2,
                'is_popular' => false,
                'number_of_dynamic_qrcodes' => 17,
                'number_of_scans' => 17000,
                'number_of_custom_domains' => 3,
                'qr_types' => QRCode::getTypes(),
                'features' => [
                    'qrcode.copy',
                    'shape.none',
                    'shape.circle',
                    'shape.cloud',
                    'shape.shopping-cart',
                    'shape.gift'
                ],
            ];
        });
    }
}
