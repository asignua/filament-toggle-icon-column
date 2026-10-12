<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\App\Models\Product;
use Workbench\App\Models\User;

/**
 * Screenshot data (`vendor/bin/testbench db:seed --class='Workbench\Database\Seeders\DemoSeeder'` after
 * `workbench:build`). Log in as emma@example.com / password.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(['email' => 'emma@example.com'], ['name' => 'Emma Carter', 'password' => 'password']);

        // [name, sku, price in cents, published, featured, archived]
        $products = [
            ['Corrugated box 40x30x20', 'BOX-4030-20', 189, true, true, false],
            ['Euro pallet EPAL', 'PAL-EPAL-01', 1450, true, false, false],
            ['Stretch film 500 mm', 'FLM-500-23', 899, true, true, false],
            ['Thermal label roll 100x150', 'LBL-100150', 1275, true, false, false],
            ['Packing tape, brown', 'TPE-BRN-48', 249, false, false, false],
            ['Bubble wrap 50 m', 'BBW-50M-01', 2190, false, true, false],
            ['Paper filler 10 kg', 'PPR-FIL-10', 1620, true, false, false],
            ['Wooden crate 60x40', 'CRT-WD-6040', 3490, false, false, true],
            ['Strapping band PP 12 mm', 'STR-PP-12', 1180, true, false, true],
        ];

        foreach ($products as [$name, $sku, $price, $published, $featured, $archived]) {
            Product::query()->firstOrCreate(['sku' => $sku], [
                'name' => $name,
                'price_cents' => $price,
                'is_published' => $published,
                'is_featured' => $featured,
                'is_archived' => $archived,
            ]);
        }
    }
}
