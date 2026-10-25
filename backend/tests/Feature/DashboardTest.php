<?php

namespace Tests\Feature;

use App\Livewire\Anomalies;
use App\Livewire\DeviceDetail;
use App\Livewire\Devices;
use App\Livewire\Events;
use App\Livewire\PosSimulator;
use App\Livewire\Products;
use App\Livewire\Reconciliation;
use App\Livewire\Settings;
use App\Livewire\Users;
use App\Enums\PermissionName;
use App\Models\Anomaly;
use App\Models\AppSetting;
use App\Models\Device;
use App\Models\Product;
use App\Models\Role;
use App\Models\ShelfEvent;
use App\Models\User;
use App\Models\WeightReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_admin_can_sign_in_and_open_the_shell(): void
    {
        $this->seed();

        $this->post('/login', [
            'email' => 'admin@ritelin.local',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Halo, Admin Ritelin')
            ->assertSee('indikasi aktivitas rak');

        $this->get(route('dashboard.devices'))
            ->assertOk()
            ->assertSee('SHELF-001')
            ->assertSee('Parfum contoh')
            ->assertSee('Perangkat baru');

        $this->get(route('dashboard.users'))
            ->assertOk()
            ->assertSee('Petugas Toko')
            ->assertSee('Pemilik Toko')
            ->assertSee('Superadmin');
    }

    public function test_petugas_can_monitor_the_shelf_but_not_manage_it(): void
    {
        $this->seed();
        $petugas = User::query()->where('email', 'petugas@ritelin.local')->firstOrFail();

        $this->actingAs($petugas)->get(route('dashboard.devices'))
            ->assertOk()
            ->assertDontSee('Perangkat baru');
        $this->actingAs($petugas)->get(route('dashboard.events'))->assertOk();
        $this->actingAs($petugas)->get(route('dashboard.pos'))->assertForbidden();
        $this->actingAs($petugas)->get(route('dashboard.users'))->assertForbidden();

        Livewire::actingAs($petugas)->test(Devices::class)
            ->set('code', 'SHELF-009')
            ->set('name', 'Rak terlarang')
            ->call('createDevice')
            ->assertForbidden();
    }

    public function test_admin_sees_shelf_activity_without_sale_language(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@ritelin.local')->firstOrFail();
        $device = Device::query()->where('code', 'SHELF-001')->firstOrFail();
        $device->forceFill([
            'last_seen_at' => now(),
            'last_weight_grams' => 80,
        ])->save();
        ShelfEvent::query()->create([
            'device_id' => $device->id,
            'event_id' => 'evt-000123',
            'event_type' => 'ITEM_REMOVED',
            'weight_grams' => 12.4,
            'occurred_at' => now(),
            'match_status' => 'pending',
            'match_note' => 'Menunggu transaksi di dalam jendela waktu.',
        ]);

        $this->actingAs($admin)->get(route('dashboard.devices'))
            ->assertOk()
            ->assertSee('indikasi aktivitas rak')
            ->assertSee('SHELF-001')
            ->assertSee('Online')
            ->assertDontSee('terjual')
            ->assertDontSee('dicuri')
            ->assertDontSee('hilang');

        $this->actingAs($admin)->get(route('dashboard.events'))
            ->assertOk()
            ->assertSee('Barang diambil')
            ->assertSee('Menunggu jendela waktu')
            ->assertDontSee('terjual');
    }

    public function test_admin_creates_a_device_with_its_own_token_and_maps_a_product(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@ritelin.local')->firstOrFail();
        $product = Product::query()->create(['name' => 'Skincare', 'sku' => 'SKIN-01']);

        $component = Livewire::actingAs($admin)->test(Devices::class)
            ->set('code', 'SHELF-002')
            ->set('name', 'Rak skincare')
            ->call('createDevice');

        $token = $component->get('issuedToken');
        $this->assertSame(40, strlen($token));

        $device = Device::query()->where('code', 'SHELF-002')->firstOrFail();
        $this->assertSame(hash('sha256', $token), $device->token_hash);

        $component->set('assignments.'.$device->id, (string) $product->id);
        $this->assertSame($product->id, $device->fresh()->product_id);

        $component->call('openMapping', $device->id)
            ->set('deviceName', 'Rak depan')
            ->set('storeLocation', 'Toko utama')
            ->set('slotLabel', 'A1')
            ->set('productId', (string) $product->id)
            ->call('saveMapping')
            ->assertHasNoErrors();

        $device->refresh();
        $this->assertSame('SHELF-002', $device->code);
        $this->assertSame('Rak depan', $device->name);
        $this->assertSame('Toko utama', $device->store_location);
        $this->assertSame('A1', $device->slot_label);
        $this->assertSame($product->id, $device->product_id);

        Livewire::actingAs($admin)->test(Devices::class)
            ->set('code', 'SHELF-003')
            ->set('name', 'Rak baru')
            ->set('storeLocation', 'Toko selatan')
            ->set('slotLabel', 'B2')
            ->set('productId', (string) $product->id)
            ->call('createDevice')
            ->assertHasNoErrors();

        $created = Device::query()->where('code', 'SHELF-003')->firstOrFail();
        $this->assertSame('Toko selatan', $created->store_location);
        $this->assertSame('B2', $created->slot_label);

        Livewire::actingAs($admin)->test(Devices::class)
            ->call('openDelete', $created->id)
            ->call('deleteDevice')
            ->assertHasNoErrors();
        $this->assertNull(Device::query()->find($created->id));
    }

    public function test_simulator_records_a_transaction_for_the_mapped_product(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@ritelin.local')->firstOrFail();
        $product = Product::query()->where('sku', 'PARFUM-DEMO')->firstOrFail();
        $device = Device::query()->where('code', 'SHELF-001')->firstOrFail();
        ShelfEvent::query()->create([
            'device_id' => $device->id,
            'event_id' => 'evt-000123',
            'event_type' => 'ITEM_REMOVED',
            'weight_grams' => 12.4,
            'occurred_at' => now(),
        ]);

        Livewire::actingAs($admin)->test(PosSimulator::class)
            ->set('productId', (string) $product->id)
            ->set('quantity', 1)
            ->call('record')
            ->assertHasNoErrors();

        $this->actingAs($admin)->get(route('dashboard.events'))->assertSee('Cocok dengan transaksi');
    }

    public function test_admin_reviews_an_anomaly_without_theft_language(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@ritelin.local')->firstOrFail();
        $device = Device::query()->where('code', 'SHELF-001')->firstOrFail();
        ShelfEvent::query()->create([
            'device_id' => $device->id,
            'event_id' => 'evt-anomali',
            'event_type' => 'ITEM_REMOVED',
            'weight_grams' => 40,
            'occurred_at' => now()->subHour(),
            'match_status' => 'unmatched',
        ]);

        $this->actingAs($admin)->get(route('dashboard.anomalies'))
            ->assertOk()
            ->assertSee('Anomali — perlu pemeriksaan')
            ->assertDontSee('pencurian')
            ->assertDontSee('dicuri');

        $anomaly = Anomaly::query()->where('shelf_event_id', ShelfEvent::query()->where('event_id', 'evt-anomali')->value('id'))->firstOrFail();

        Livewire::actingAs($admin)->test(Anomalies::class)
            ->call('inspect', $anomaly->id)
            ->set('followStatus', 'ignored')
            ->call('saveFollowUp')
            ->assertHasErrors('ignoreReason')
            ->set('ignoreReason', 'Pembacaan diulang saat isi ulang rak')
            ->call('saveFollowUp')
            ->assertHasNoErrors();

        $this->assertSame('ignored', $anomaly->fresh()->status->value);
    }

    public function test_admin_filters_activity_and_exports_a_report(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@ritelin.local')->firstOrFail();
        $device = Device::query()->where('code', 'SHELF-001')->firstOrFail();
        ShelfEvent::query()->create([
            'device_id' => $device->id,
            'event_id' => 'evt-removed',
            'event_type' => 'ITEM_REMOVED',
            'weight_grams' => 20,
            'occurred_at' => now(),
        ]);
        ShelfEvent::query()->create([
            'device_id' => $device->id,
            'event_id' => 'evt-returned',
            'event_type' => 'ITEM_RETURNED',
            'weight_grams' => 80,
            'occurred_at' => now(),
        ]);

        Livewire::actingAs($admin)->test(Events::class)
            ->set('eventType', 'ITEM_RETURNED')
            ->call('apply')
            ->assertSee('evt-returned')
            ->assertDontSee('evt-removed');

        Livewire::actingAs($admin)->test(Products::class)
            ->set('name', 'Sabun')
            ->set('sku', 'SABUN-01')
            ->set('category', 'Mandi')
            ->set('salePrice', '12000')
            ->set('unitWeight', '90')
            ->call('saveProduct')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('products', ['sku' => 'SABUN-01', 'category' => 'Mandi']);

        Livewire::actingAs($admin)->test(Settings::class)
            ->set('offlineSeconds', '120')
            ->set('thresholdGrams', '20')
            ->set('matchLeadMinutes', '2')
            ->set('matchWindowMinutes', '10')
            ->set('stockWindowMinutes', '15')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(120, AppSetting::offlineSeconds());
        $this->assertSame(15, AppSetting::stockWindowMinutes());
        $this->assertSame(10, AppSetting::matchWindowMinutes());
        $this->assertSame(2, AppSetting::matchLeadMinutes());

        $response = $this->actingAs($admin)->get(route('dashboard.reports.csv'));
        $response->assertOk();
        $this->assertStringContainsString('harian', $response->streamedContent());
    }

    public function test_admin_can_calibrate_a_device_and_record_a_verification_note(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@ritelin.local')->firstOrFail();
        $device = Device::query()->where('code', 'SHELF-001')->firstOrFail();
        $event = ShelfEvent::query()->create([
            'device_id' => $device->id,
            'event_id' => 'evt-note',
            'event_type' => 'ITEM_REMOVED',
            'weight_grams' => 20,
            'occurred_at' => now(),
            'match_status' => 'needs_review',
        ]);

        Livewire::actingAs($admin)->test(DeviceDetail::class, ['device' => $device])
            ->call('calibrate')
            ->call('toggleActive');

        $device->refresh();
        $this->assertNotNull($device->last_calibrated_at);
        $this->assertFalse($device->is_active);

        Livewire::actingAs($admin)->test(Reconciliation::class)
            ->call('editNote', $event->id)
            ->set('staffNote', 'Dicek saat isi ulang')
            ->call('saveNote')
            ->assertHasNoErrors();

        $this->assertSame('Dicek saat isi ulang', $event->fresh()->staff_note);
    }

    public function test_petugas_can_open_monitoring_pages_but_not_settings(): void
    {
        $this->seed();
        $petugas = User::query()->where('email', 'petugas@ritelin.local')->firstOrFail();

        $this->actingAs($petugas)->get(route('dashboard.sensors'))->assertOk();
        $this->actingAs($petugas)->get(route('dashboard.anomalies'))->assertOk();
        $this->actingAs($petugas)->get(route('dashboard.reconciliation'))->assertOk();
        $this->actingAs($petugas)->get(route('dashboard.reports'))
            ->assertOk()
            ->assertSee('Laporan & Analisis')
            ->assertSee('Aktivitas harian');
        $this->actingAs($petugas)->get(route('dashboard.products'))->assertOk()->assertDontSee('Simpan produk');
        $this->actingAs($petugas)->get(route('dashboard.settings'))->assertForbidden();
        $this->actingAs($petugas)->get(route('dashboard.users'))->assertForbidden();
    }

    public function test_only_the_highest_role_can_create_and_delete_users(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@ritelin.local')->firstOrFail();
        $petugas = User::query()->where('email', 'petugas@ritelin.local')->firstOrFail();

        Livewire::actingAs($petugas)->test(Users::class)->assertForbidden();

        Livewire::actingAs($admin)->test(Users::class)
            ->set('name', 'Kasir Baru')
            ->set('email', 'kasir@ritelin.local')
            ->set('password', 'password1')
            ->set('role', 'staff')
            ->call('saveUser')
            ->assertHasNoErrors();

        $created = User::query()->where('email', 'kasir@ritelin.local')->firstOrFail();
        $this->assertTrue($created->hasRole('staff'));

        Livewire::actingAs($admin)->test(Users::class)
            ->call('openDelete', $created->id)
            ->call('deleteUser')
            ->assertHasNoErrors();

        $this->assertNull(User::query()->find($created->id));
    }

    public function test_highest_role_can_change_permissions_without_dropping_account_control(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@ritelin.local')->firstOrFail();
        $staff = Role::query()->where('name', 'staff')->firstOrFail();

        Livewire::actingAs($admin)->test(Users::class)
            ->set('bagian', 'peran')
            ->call('togglePermission', 'staff', PermissionName::PosManage->value)
            ->assertHasNoErrors();

        $this->assertTrue($staff->permissions()->where('name', PermissionName::PosManage->value)->exists());

        Livewire::actingAs($admin)->test(Users::class)
            ->call('togglePermission', 'superadmin', PermissionName::UsersManage->value)
            ->assertHasErrors('roles');

        $this->assertTrue(
            Role::query()->where('name', 'superadmin')->firstOrFail()
                ->permissions()->where('name', PermissionName::UsersManage->value)->exists()
        );
    }

    public function test_demo_seed_leaves_an_existing_shelf_001_untouched(): void
    {
        $this->seed();
        $device = Device::query()->where('code', 'SHELF-001')->firstOrFail();
        $device->name = 'Rak yang sudah ada';
        $device->store_location = 'Lokasi tetap';
        $device->slot_label = 'Z9';
        $device->save();
        $hash = $device->token_hash;
        $device->readings()->delete();
        WeightReading::query()->create([
            'device_id' => $device->id,
            'weight_grams' => 12.5,
            'recorded_at' => now(),
            'source' => 'heartbeat',
        ]);

        $this->seed();

        $fresh = $device->fresh();
        $this->assertSame('Rak yang sudah ada', $fresh->name);
        $this->assertSame('Lokasi tetap', $fresh->store_location);
        $this->assertSame('Z9', $fresh->slot_label);
        $this->assertSame($hash, $fresh->token_hash);
        $this->assertSame(1, $fresh->readings()->count());
        $this->assertSame('12.5', (string) $fresh->readings()->value('weight_grams'));
        $this->assertNotNull(Device::query()->where('code', 'SHELF-010')->first());
        $this->assertNotNull(Device::query()->where('code', 'SHELF-012')->first());
        $this->assertSame(0, ShelfEvent::query()->where('device_id', $device->id)->where('event_id', 'like', 'evt-demo-%')->count());
    }
}
