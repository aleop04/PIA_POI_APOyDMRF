<?php

namespace Database\Seeders;

use App\Models\Reward;
use Illuminate\Database\Seeder;

class RewardSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            ['name' => 'Insignia Alebrije', 'image' => 'rewards/Alebrije.jpg'],
            ['name' => 'Insignia Cactus', 'image' => 'rewards/Cactus.jpg'],
            ['name' => 'Insignia Calavera', 'image' => 'rewards/Calavera.jpg'],
            ['name' => 'Insignia Cempasúchil', 'image' => 'rewards/Cempasuchil.jpg'],
            ['name' => 'Insignia Concha', 'image' => 'rewards/Concha.jpg'],
            ['name' => 'Insignia Flor', 'image' => 'rewards/Flor.jpg'],
            ['name' => 'Insignia Guitarra', 'image' => 'rewards/Guitarra.jpg'],
            ['name' => 'Insignia Mariachi', 'image' => 'rewards/Mariachi.jpg'],
            ['name' => 'Insignia Muñeca', 'image' => 'rewards/Muneca.jpg'],
            ['name' => 'Insignia Papel', 'image' => 'rewards/Papel.jpg'],
            ['name' => 'Insignia Pozole', 'image' => 'rewards/Pozole.jpg'],
            ['name' => 'Insignia Salsa', 'image' => 'rewards/Salsa.jpg'],
            ['name' => 'Insignia Tacos', 'image' => 'rewards/Tacos.jpg'],
            ['name' => 'Insignia Vela', 'image' => 'rewards/Vela.jpg'],
            ['name' => 'Insignia Sombrero', 'image' => 'rewards/Sombrero.jpg'],
            ['name' => 'Insignia Elote', 'image' => 'rewards/Elote.jpg'],
            ['name' => 'Insignia Piñata', 'image' => 'rewards/Pinata.jpg'],
            ['name' => 'Insignia Mariposa', 'image' => 'rewards/Mariposa.jpg'],
        ];

        foreach ($badges as $badge) {
            Reward::updateOrCreate(
                [
                    'name' => $badge['name'],
                    'type' => 'badge',
                ],
                [
                    'description' => 'Insignia coleccionable para mostrar en tu perfil.',
                    'cost_points' => 5,
                    'stock' => null,
                    'image' => $badge['image'],
                    'discount_value' => null,
                    'is_active' => true,
                ]
            );
        }

        $discounts = [
            [
                'name' => '30% de descuento',
                'cost_points' => 50,
                'discount_value' => 30,
            ],
            [
                'name' => '50% de descuento',
                'cost_points' => 150,
                'discount_value' => 50,
            ],
            [
                'name' => '75% de descuento',
                'cost_points' => 200,
                'discount_value' => 75,
            ],
            [
                'name' => '100% de descuento',
                'cost_points' => 500,
                'discount_value' => 100,
            ],
        ];

        foreach ($discounts as $discount) {
            Reward::updateOrCreate(
                [
                    'name' => $discount['name'],
                    'type' => 'discount',
                ],
                [
                    'description' => 'Descuento canjeable con tus puntos.',
                    'cost_points' => $discount['cost_points'],
                    'stock' => null,
                    'image' => 'rewards/destinariologo1.png',
                    'discount_value' => $discount['discount_value'],
                    'is_active' => true,
                ]
            );
        }
    }
}