<?php

namespace Tests\Feature;

use App\Events\DashboardUpdated;
use App\Models\Anomaly;
use App\Models\Device;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\ShelfEvent;
use App\Services\ShelfMatcher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ShelfApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'rakku.pos_token' => 'pos-secret',
            'rakku.match_window_minutes' => 10,
            'rakku.match_lead_minutes' => 2,
            'rakku.stock_window_minutes' => 2,
        ]);
    }

    public function test_event_is_stored_once_when_the_same_id_is_retried(): void
    {
        $device = $this->device();
        $payload = $this->eventPayload();

        $this->postJson('/api/events', $payload, $this->deviceHeaders())
            ->assertCreated()
            ->assertJsonPath('duplicate', false)
            ->assertJsonPath('data.event_id', 'evt-000123');

        $this->postJson('/api/events', $payload, $this->deviceHeaders())
            ->assertOk()
            ->assertJsonPath('duplicate', true);

        $this->assertSame(1, ShelfEvent::query()->count());
        $this->assertSame(1, \App\Models\WeightReading::query()->count());
        $this->assertNull(ShelfEvent::query()->first()->weight_before_grams);
        $device->refresh();
        $this->assertNotNull($device->last_seen_at);
        $this->assertSame('0.7.0', $device->firmware_version);
        $this->assertSame('12.4', $device->last_weight_grams);
    }

    public function test_unknown_device_and_invalid_payload_are_rejected(): void
    {
        $this->device();

        $this->postJson('/api/events', $this->eventPayload(), [
            'Authorization' => 'Bearer salah',
            'Accept' => 'application/json',
        ])->assertUnauthorized();

        $this->postJson('/api/events', ['device_id' => 'SHELF-001'], $this->deviceHeaders())
            ->assertUnprocessable();

        $this->postJson('/api/events', $this->eventPayload('SHELF-999'), $this->deviceHeaders())
            ->assertUnprocessable();

        $this->assertSame(2, \App\Models\ProcessingFailure::query()->count());
    }

    public function test_public_network_requires_https_when_enabled(): void
    {
        $this->device();
        config(['rakku.require_tls' => true]);

        $this->postJson('/api/events', $this->eventPayload(), $this->deviceHeaders())
            ->assertStatus(400);
    }

    public function test_config_marks_the_device_seen(): void
    {
        $device = $this->device();

        $this->getJson('/api/config', $this->deviceHeaders())
            ->assertOk()
            ->assertJsonPath('device_id', 'SHELF-001')
            ->assertJsonPath('threshold_grams', 15);

        $this->assertNotNull($device->fresh()->last_seen_at);
    }

    public function test_matching_covers_sale_return_and_review(): void
    {
        $product = Product::query()->create(['name' => 'Parfum', 'sku' => 'PARFUM-DEMO']);
        $device = $this->device($product);
        Carbon::setTestNow('2026-10-23 01:30:00');

        $this->postJson('/api/events', $this->eventPayload(), $this->deviceHeaders())->assertCreated();
        $open = ShelfEvent::query()->first();
        $this->assertSame('pending', $open->fresh()->match_status);

        $this->postJson('/api/pos/transactions', [
            'external_id' => 'POS-1',
            'sku' => 'PARFUM-DEMO',
            'quantity' => 1,
            'timestamp' => '2026-10-23T01:35:00Z',
        ], ['X-Pos-Token' => 'pos-secret', 'Accept' => 'application/json'])->assertCreated();

        $this->assertSame('matched', $open->fresh()->match_status);

        $this->postJson('/api/pos/transactions', [
            'external_id' => 'POS-1',
            'sku' => 'PARFUM-DEMO',
            'quantity' => 1,
            'timestamp' => '2026-10-23T01:35:00Z',
        ], ['X-Pos-Token' => 'pos-secret', 'Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('duplicate', true);

        $this->assertSame(1, PosTransaction::query()->count());

        $second = $this->device($product, 'SHELF-002', 'token-2');
        $this->postJson('/api/events', $this->eventPayload('SHELF-002', 'evt-000200', '2026-10-23T02:00:00Z'), [
            'Authorization' => 'Bearer token-2',
            'Accept' => 'application/json',
        ])->assertCreated();
        $this->postJson('/api/events', [
            'device_id' => 'SHELF-002',
            'event_id' => 'evt-000201',
            'event_type' => 'ITEM_RETURNED',
            'weight_grams' => 80,
            'timestamp' => '2026-10-23T02:02:00Z',
        ], [
            'Authorization' => 'Bearer token-2',
            'Accept' => 'application/json',
        ])->assertCreated();

        $this->assertSame('returned', ShelfEvent::query()->where('event_id', 'evt-000200')->first()->resolution);

        $third = $this->device($product, 'SHELF-003', 'token-3');
        $this->postJson('/api/events', $this->eventPayload('SHELF-003', 'evt-000300', '2026-10-23T03:00:00Z'), [
            'Authorization' => 'Bearer token-3',
            'Accept' => 'application/json',
        ])->assertCreated();
        PosTransaction::query()->create([
            'external_id' => 'POS-2',
            'product_id' => $product->id,
            'quantity' => 2,
            'occurred_at' => '2026-10-23T03:01:00Z',
            'source' => 'webhook',
        ]);
        app(ShelfMatcher::class)->reconcile($third);

        $this->assertSame('needs_review', ShelfEvent::query()->where('event_id', 'evt-000300')->first()->match_status);

        Carbon::setTestNow('2026-10-23 04:20:00');
        $fourth = $this->device($product, 'SHELF-004', 'token-4');
        ShelfEvent::query()->create([
            'device_id' => $fourth->id,
            'event_id' => 'evt-000400',
            'event_type' => 'ITEM_REMOVED',
            'weight_grams' => 10,
            'occurred_at' => '2026-10-23T04:00:00Z',
        ]);
        app(ShelfMatcher::class)->reconcile($fourth);

        $this->assertSame('unmatched', ShelfEvent::query()->where('event_id', 'evt-000400')->first()->match_status);
    }

    public function test_physical_stock_uses_floor_and_matches_a_pos_drop(): void
    {
        $product = Product::query()->create([
            'name' => 'Parfum',
            'sku' => 'PARFUM-DEMO',
            'unit_weight_grams' => 80,
        ]);
        $device = $this->device($product);
        $device->forceFill(['baseline_weight_grams' => 0])->save();
        Carbon::setTestNow('2026-10-23 01:30:00');

        $this->assertSame(2, \App\Services\PhysicalStock::units(232, 80));
        $this->assertSame(2, \App\Services\PhysicalStock::units(239, 80));
        $this->assertSame(1, \App\Services\PhysicalStock::units(95, 80));

        $this->postJson('/api/events', $this->weighed('evt-stock-1', 232), $this->deviceHeaders())->assertCreated();
        $device->refresh();
        $this->assertSame(2, $device->physical_stock);
        $this->assertSame(2, $device->pos_stock);
        $this->assertSame('aligned', $device->stock_status);

        $this->postJson('/api/events', $this->weighed('evt-stock-2', 80, '2026-10-23T01:31:00Z'), $this->deviceHeaders())->assertCreated();
        $device->refresh();
        $this->assertSame(1, $device->physical_stock);
        $this->assertSame(2, $device->pos_stock);
        $this->assertSame('awaiting', $device->stock_status);

        $this->postJson('/api/pos/transactions', [
            'external_id' => 'POS-STOCK',
            'sku' => 'PARFUM-DEMO',
            'quantity' => 1,
            'timestamp' => '2026-10-23T01:32:00Z',
        ], ['X-Pos-Token' => 'pos-secret', 'Accept' => 'application/json'])->assertCreated();

        $device->refresh();
        $this->assertSame(1, $device->pos_stock);
        $this->assertSame('aligned', $device->stock_status);
    }

    public function test_unmatched_stock_drop_opens_a_review_without_theft_words(): void
    {
        $product = Product::query()->create([
            'name' => 'Parfum',
            'sku' => 'PARFUM-DEMO',
            'unit_weight_grams' => 80,
        ]);
        $device = $this->device($product);
        $device->forceFill(['baseline_weight_grams' => 0])->save();
        Carbon::setTestNow('2026-10-23 02:00:00');

        $this->postJson('/api/events', $this->weighed('evt-gap-1', 160, '2026-10-23T02:00:00Z'), $this->deviceHeaders())->assertCreated();
        $this->postJson('/api/events', $this->weighed('evt-gap-2', 80, '2026-10-23T02:01:00Z'), $this->deviceHeaders())->assertCreated();

        Carbon::setTestNow('2026-10-23 02:04:00');
        app(\App\Services\AnomalySync::class)->sync();

        $device->refresh();
        $this->assertSame('review', $device->stock_status);
        $this->assertSame(1, $device->stockGapUnits());
        $anomaly = Anomaly::query()->where('kind', 'stock_gap')->first();
        $this->assertNotNull($anomaly);
        $this->assertSame('Anomali — perlu pemeriksaan', $anomaly->title);
        $this->assertStringNotContainsString('hilang', $anomaly->summary);
        $this->assertStringNotContainsString('dicuri', $anomaly->summary);
    }

    public function test_dashboard_tare_is_polled_then_stored(): void
    {
        $product = Product::query()->create([
            'name' => 'Parfum',
            'sku' => 'PARFUM-DEMO',
            'unit_weight_grams' => 80,
        ]);
        $device = $this->device($product);
        $device->forceFill(['tare_requested' => true, 'baseline_weight_grams' => 40])->save();

        $this->getJson('/api/config', $this->deviceHeaders())
            ->assertOk()
            ->assertJsonPath('tare', true)
            ->assertJsonPath('threshold_grams', 15);

        $this->postJson('/api/tare', [
            'device_id' => 'SHELF-001',
            'weight_grams' => 0,
        ], $this->deviceHeaders())->assertOk();

        $device->refresh();
        $this->assertFalse($device->tare_requested);
        $this->assertSame('0.0', $device->baseline_weight_grams);
        $this->assertSame(0, $device->physical_stock);
        $this->assertSame(0, $device->pos_stock);
    }

    public function test_new_shelf_data_notifies_open_dashboards(): void
    {
        $this->device();
        Event::fake([DashboardUpdated::class]);

        $this->postJson('/api/events', $this->eventPayload(), $this->deviceHeaders())
            ->assertCreated();

        Event::assertDispatched(DashboardUpdated::class);
    }

    private function device(?Product $product = null, string $code = 'SHELF-001', string $token = 'device-token'): Device
    {
        return Device::query()->create([
            'code' => $code,
            'name' => 'Rak uji',
            'token_hash' => hash('sha256', $token),
            'product_id' => $product?->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function eventPayload(
        string $code = 'SHELF-001',
        string $eventId = 'evt-000123',
        string $timestamp = '2026-10-23T01:30:00Z',
    ): array {
        return [
            'device_id' => $code,
            'event_id' => $eventId,
            'event_type' => 'ITEM_REMOVED',
            'weight_grams' => 12.4,
            'timestamp' => $timestamp,
        ];
    }

    /**
     * @return array<string, string>
     */
    /**
     * @return array<string, mixed>
     */
    private function weighed(string $eventId, float $grams, string $timestamp = '2026-10-23T01:30:00Z'): array
    {
        return [
            'device_id' => 'SHELF-001',
            'event_id' => $eventId,
            'event_type' => 'ITEM_REMOVED',
            'weight_grams' => $grams,
            'timestamp' => $timestamp,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function deviceHeaders(): array
    {
        return [
            'Authorization' => 'Bearer device-token',
            'Accept' => 'application/json',
            'X-Firmware-Version' => '0.7.0',
        ];
    }
}
