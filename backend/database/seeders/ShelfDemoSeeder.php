<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Product;
use App\Models\WeightReading;
use Illuminate\Database\Seeder;

class ShelfDemoSeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::query()->updateOrCreate(
            ['sku' => 'PARFUM-DEMO'],
            [
                'name' => 'Parfum contoh',
                'category' => 'Parfum',
                'unit_weight_grams' => 80,
                'sale_price' => 45000,
                'is_active' => true,
            ],
        );

        $device = Device::query()->updateOrCreate(
            ['code' => 'SHELF-001'],
            [
                'name' => 'Rak parfum demo',
                'store_location' => 'Toko demo',
                'slot_label' => 'A1',
                'is_active' => true,
                'token_hash' => hash('sha256', (string) env('DEMO_DEVICE_TOKEN', 'local-shelf-001-token')),
                'product_id' => $product->id,
                'firmware_version' => '0.7.0',
                'last_seen_at' => now(),
                'last_weight_grams' => 80,
                'baseline_weight_grams' => 80,
                'shelf_status' => 'normal',
                'sensor_status' => 'stable',
                'last_calibrated_at' => now()->subDay(),
            ],
        );

        $device->readings()->delete();
        foreach ([78.4, 79.1, 80.0, 80.2, 80.0] as $offset => $grams) {
            WeightReading::query()->create([
                'device_id' => $device->id,
                'weight_grams' => $grams,
                'recorded_at' => now()->subMinutes(50 - ($offset * 10)),
                'source' => 'heartbeat',
            ]);
        }
    }
}
