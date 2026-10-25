<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ShelfDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}
