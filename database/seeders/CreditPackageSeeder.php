<?php

namespace Database\Seeders;

use App\Models\CreditPackage;
use Illuminate\Database\Seeder;

class CreditPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'credits' => 50,
                'price_try' => 199.00,
                'price_eur' => 19.00,
                'description' => 'Kucuk isletmeler icin ideal baslangic paketi',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'credits' => 150,
                'price_try' => 499.00,
                'price_eur' => 49.00,
                'description' => 'Buyuyen isletmeler icin en populer secim',
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'credits' => 400,
                'price_try' => 999.00,
                'price_eur' => 99.00,
                'description' => 'Orta olcekli isletmeler icin tasarruflu paket',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'credits' => 1000,
                'price_try' => 1999.00,
                'price_eur' => 199.00,
                'description' => 'Buyuk isletmeler icin maksimum tasarruf',
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 4,
            ],
        ];

        foreach ($packages as $package) {
            CreditPackage::updateOrCreate(
                ['slug' => $package['slug']],
                $package
            );
        }

        $this->command->info('Credit packages seeded successfully!');
    }
}
