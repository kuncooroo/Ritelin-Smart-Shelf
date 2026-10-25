<?php

namespace Database\Seeders;

use App\Enums\AnomalyKind;
use App\Enums\AnomalyPriority;
use App\Enums\AnomalyStatus;
use App\Enums\RoleName;
use App\Models\Anomaly;
use App\Models\Device;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\Role;
use App\Models\ShelfEvent;
use App\Models\User;
use App\Models\WeightReading;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAccounts();
        $products = $this->seedProducts();
        $this->seedWorkingShelf($products['PARFUM-DEMO']);
        $devices = $this->seedExtraShelves($products);
        $this->seedHistory($devices, $products);
    }

    private function seedAccounts(): void
    {
        $accounts = config('ops.demo', []);
        if (! is_array($accounts)) {
            return;
        }

        foreach ($accounts as $account) {
            if (! is_array($account)) {
                continue;
            }

            $email = $account['email'] ?? null;
            $name = $account['name'] ?? null;
            $password = $account['password'] ?? null;
            $roleName = $account['role'] ?? null;
            $role = is_string($roleName) ? RoleName::tryFrom($roleName) : null;
            if (! is_string($email) || $email === '' || ! is_string($name) || $name === '' || ! is_string($password) || $password === '' || $role === null) {
                continue;
            }

            $roleRow = Role::query()->where('name', $role->value)->firstOrFail();
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => $password,
                    'is_active' => true,
                ],
            );
            $user->roles()->sync([$roleRow->id => ['created_at' => now()]]);
        }
    }

    /**
     * @return array<string, Product>
     */
    private function seedProducts(): array
    {
        $rows = [
            'PARFUM-DEMO' => ['name' => 'Parfum contoh', 'category' => 'Parfum', 'unit_weight_grams' => 80, 'sale_price' => 45000],
            'SABUN-DEMO' => ['name' => 'Sabun contoh', 'category' => 'Sabun', 'unit_weight_grams' => 90, 'sale_price' => 12000],
            'SNACK-DEMO' => ['name' => 'Snack contoh', 'category' => 'Makanan', 'unit_weight_grams' => 40, 'sale_price' => 8000],
            'MINUM-DEMO' => ['name' => 'Minuman contoh', 'category' => 'Minuman', 'unit_weight_grams' => 250, 'sale_price' => 6000],
            'BERAS-DEMO' => ['name' => 'Beras contoh', 'category' => 'Sembako', 'unit_weight_grams' => 1000, 'sale_price' => 15000],
        ];

        $products = [];
        foreach ($rows as $sku => $row) {
            $products[$sku] = Product::query()->firstOrCreate(
                ['sku' => $sku],
                $row + ['is_active' => true],
            );
        }

        return $products;
    }

    private function seedWorkingShelf(Product $product): void
    {
        $device = Device::query()->firstOrCreate(
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

        if (! $device->wasRecentlyCreated || $device->readings()->exists()) {
            return;
        }

        foreach ([78.4, 79.1, 80.0, 80.2, 80.0] as $offset => $grams) {
            WeightReading::query()->create([
                'device_id' => $device->id,
                'weight_grams' => $grams,
                'recorded_at' => now()->subMinutes(50 - ($offset * 10)),
                'source' => 'heartbeat',
            ]);
        }
    }

    /**
     * @param  array<string, Product>  $products
     * @return array<string, Device>
     */
    private function seedExtraShelves(array $products): array
    {
        $rows = [
            'SHELF-010' => ['name' => 'Rak sabun demo', 'slot_label' => 'B1', 'token' => 'demo-shelf-010-token', 'sku' => 'SABUN-DEMO'],
            'SHELF-011' => ['name' => 'Rak snack demo', 'slot_label' => 'B2', 'token' => 'demo-shelf-011-token', 'sku' => 'SNACK-DEMO'],
            'SHELF-012' => ['name' => 'Rak minuman demo', 'slot_label' => 'C1', 'token' => 'demo-shelf-012-token', 'sku' => 'MINUM-DEMO'],
        ];

        $devices = [];
        foreach ($rows as $code => $row) {
            $devices[$code] = Device::query()->firstOrCreate(
                ['code' => $code],
                [
                    'name' => $row['name'],
                    'store_location' => 'Toko demo',
                    'slot_label' => $row['slot_label'],
                    'is_active' => true,
                    'token_hash' => hash('sha256', $row['token']),
                    'product_id' => $products[$row['sku']]->id,
                    'firmware_version' => null,
                    'shelf_status' => 'normal',
                    'sensor_status' => 'unknown',
                ],
            );
        }

        return $devices;
    }

    /**
     * @param  array<string, Device>  $devices
     * @param  array<string, Product>  $products
     */
    private function seedHistory(array $devices, array $products): void
    {
        $rows = [
            ['code' => 'SHELF-010', 'event_id' => 'evt-demo-010-1', 'type' => 'ITEM_REMOVED', 'days' => 1, 'status' => 'matched', 'sku' => 'SABUN-DEMO'],
            ['code' => 'SHELF-010', 'event_id' => 'evt-demo-010-2', 'type' => 'ITEM_REMOVED', 'days' => 1, 'status' => 'unmatched', 'sku' => null],
            ['code' => 'SHELF-010', 'event_id' => 'evt-demo-010-3', 'type' => 'ITEM_ADDED', 'days' => 2, 'status' => null, 'sku' => null],
            ['code' => 'SHELF-011', 'event_id' => 'evt-demo-011-1', 'type' => 'ITEM_REMOVED', 'days' => 2, 'status' => 'needs_review', 'sku' => null],
            ['code' => 'SHELF-011', 'event_id' => 'evt-demo-011-2', 'type' => 'ITEM_REMOVED', 'days' => 3, 'status' => 'matched', 'sku' => 'SNACK-DEMO'],
            ['code' => 'SHELF-011', 'event_id' => 'evt-demo-011-3', 'type' => 'ITEM_RETURNED', 'days' => 4, 'status' => null, 'sku' => null, 'resolution' => 'returned'],
            ['code' => 'SHELF-012', 'event_id' => 'evt-demo-012-1', 'type' => 'ITEM_REMOVED', 'days' => 4, 'status' => 'matched', 'sku' => 'MINUM-DEMO'],
            ['code' => 'SHELF-012', 'event_id' => 'evt-demo-012-2', 'type' => 'ITEM_REMOVED', 'days' => 5, 'status' => 'unmatched', 'sku' => null],
            ['code' => 'SHELF-012', 'event_id' => 'evt-demo-012-3', 'type' => 'ITEM_REMOVED', 'days' => 6, 'status' => 'needs_review', 'sku' => null],
        ];

        foreach ($rows as $row) {
            $device = $devices[$row['code']] ?? null;
            if ($device === null) {
                continue;
            }

            $when = now()->subDays($row['days'])->setTime(10, 15);
            $transaction = null;
            if ($row['status'] === 'matched' && is_string($row['sku']) && isset($products[$row['sku']])) {
                $transaction = PosTransaction::query()->firstOrCreate(
                    ['external_id' => 'POS-DEMO-'.$row['event_id']],
                    [
                        'product_id' => $products[$row['sku']]->id,
                        'quantity' => 1,
                        'occurred_at' => $when,
                        'source' => 'webhook',
                    ],
                );
            }

            ShelfEvent::query()->firstOrCreate(
                ['device_id' => $device->id, 'event_id' => $row['event_id']],
                [
                    'event_type' => $row['type'],
                    'weight_grams' => 80,
                    'weight_before_grams' => 160,
                    'occurred_at' => $when,
                    'match_status' => $row['status'],
                    'resolution' => $row['resolution'] ?? null,
                    'pos_transaction_id' => $transaction?->id,
                    'checked_at' => now(),
                ],
            );
        }

        $anomalyDevice = $devices['SHELF-010'] ?? null;
        if ($anomalyDevice === null) {
            return;
        }

        $anomaly = Anomaly::query()->firstOrCreate(
            ['dedupe_key' => 'demo-anomaly-shelf-010'],
            [
                'device_id' => $anomalyDevice->id,
                'kind' => AnomalyKind::WeightReview,
                'title' => Anomaly::TITLE,
                'summary' => 'Perubahan berat pada rak sabun demo perlu dilihat. Ini indikasi, bukan bukti penyebab.',
                'priority' => AnomalyPriority::Medium,
                'status' => AnomalyStatus::Resolved,
                'checked_at' => now(),
            ],
        );
        if ($anomaly->wasRecentlyCreated) {
            $anomaly->created_at = now()->subDays(2);
            $anomaly->save();
        }

        $second = Anomaly::query()->firstOrCreate(
            ['dedupe_key' => 'demo-anomaly-shelf-012'],
            [
                'device_id' => ($devices['SHELF-012'] ?? $anomalyDevice)->id,
                'kind' => AnomalyKind::Offline,
                'title' => Anomaly::TITLE,
                'summary' => 'Rak minuman demo belum mengirim data. Perangkat ini belum dipasang.',
                'priority' => AnomalyPriority::Low,
                'status' => AnomalyStatus::Resolved,
                'checked_at' => now(),
            ],
        );
        if ($second->wasRecentlyCreated) {
            $second->created_at = now()->subDays(3);
            $second->save();
        }
    }
}
