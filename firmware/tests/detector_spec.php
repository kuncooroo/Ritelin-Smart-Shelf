<?php

declare(strict_types=1);

/**
 * Spesifikasi perilaku detector.h dan event_queue.h.
 * Jalankan: php firmware/tests/detector_spec.php
 */

final class ShelfDetectorSpec
{
    private const WINDOW = 5;

    private float $threshold;

    private int $verifyMs;

    /** @var array<int, float> */
    private array $window = [];

    private int $index = 0;

    private bool $filled = false;

    private bool $baselineReady = false;

    private float $baseline = 0;

    private int $changeStartedMs = 0;

    private string $state = 'NORMAL';

    public function __construct(float $threshold, int $verifyMs)
    {
        $this->threshold = $threshold;
        $this->verifyMs = $verifyMs;
    }

    public function resetBaseline(): void
    {
        $this->baselineReady = false;
        $this->baseline = 0;
        $this->filled = false;
        $this->index = 0;
        $this->changeStartedMs = 0;
        $this->state = 'NORMAL';
        $this->window = [];
    }

    /** @return array{state: string, event: string, weight: float, baseline: float} */
    public function update(int $nowMs, float $sample): array
    {
        $this->push($sample);
        $weight = $this->median();
        $event = '';

        if (! $this->baselineReady) {
            $this->baseline = $weight;
            $this->baselineReady = true;
            $this->state = 'NORMAL';

            return $this->result($weight, $event);
        }

        switch ($this->state) {
            case 'NORMAL':
                if ($this->departed($weight)) {
                    $this->state = 'CHANGE_DETECTED';
                    $this->changeStartedMs = $nowMs;
                }
                break;
            case 'CHANGE_DETECTED':
                $this->state = 'VERIFYING';
                $event = $this->evaluateVerifying($nowMs, $weight);
                break;
            case 'VERIFYING':
                $event = $this->evaluateVerifying($nowMs, $weight);
                break;
        }

        return $this->result($weight, $event);
    }

    private function evaluateVerifying(int $nowMs, float $weight): string
    {
        if (! $this->departed($weight)) {
            $this->state = 'NORMAL';

            return '';
        }
        if (($nowMs - $this->changeStartedMs) < $this->verifyMs) {
            return '';
        }
        $removed = $weight < $this->baseline;
        $this->baseline = $weight;
        $this->state = 'NORMAL';

        return $removed ? 'ITEM_REMOVED' : 'ITEM_ADDED';
    }

    private function push(float $sample): void
    {
        if (! $this->filled) {
            $this->window = array_fill(0, self::WINDOW, $sample);
            $this->filled = true;
            $this->index = 0;

            return;
        }
        $this->window[$this->index] = $sample;
        $this->index = ($this->index + 1) % self::WINDOW;
    }

    private function median(): float
    {
        $copy = $this->window;
        sort($copy);

        return $copy[intdiv(self::WINDOW, 2)];
    }

    private function departed(float $weight): bool
    {
        return abs($weight - $this->baseline) > $this->threshold;
    }

    /** @return array{state: string, event: string, weight: float, baseline: float} */
    private function result(float $weight, string $event): array
    {
        return [
            'state' => $this->state,
            'event' => $event,
            'weight' => $weight,
            'baseline' => $this->baseline,
        ];
    }
}

final class EventQueueSpec
{
    public const CAPACITY = 16;

    /** @var array<int, array{id: string, attempts: int}> */
    private array $items = [];

    public function enqueue(string $id): bool
    {
        if ($id === '') {
            return false;
        }
        foreach ($this->items as $item) {
            if ($item['id'] === $id) {
                return false;
            }
        }
        if (count($this->items) >= self::CAPACITY) {
            return false;
        }
        $this->items[] = ['id' => $id, 'attempts' => 0];

        return true;
    }

    public function markHeadAttempt(): void
    {
        if ($this->items !== []) {
            $this->items[0]['attempts']++;
        }
    }

    public function headId(): ?string
    {
        return $this->items[0]['id'] ?? null;
    }

    public function popHead(): void
    {
        array_shift($this->items);
    }

    public function size(): int
    {
        return count($this->items);
    }
}

function check(bool $condition, string $message): void
{
    if (! $condition) {
        fwrite(STDERR, "GAGAL: {$message}\n");
        exit(1);
    }
}

$detector = new ShelfDetectorSpec(15.0, 1000);
$first = $detector->update(0, 100.0);
check($first['state'] === 'NORMAL' && $first['event'] === '', 'baseline awal');

$events = 0;
foreach ([98.0, 102.0, 99.0, 101.0, 100.0, 97.0, 103.0] as $index => $weight) {
    $sample = $detector->update(1000 + ($index * 100), $weight);
    $events += $sample['event'] === '' ? 0 : 1;
}
check($events === 0 && $sample['state'] === 'NORMAL', 'noise di bawah threshold tidak membuat event');

$touch = new ShelfDetectorSpec(15.0, 1000);
$touch->update(0, 100.0);
$touch->update(100, 0.0);
$touch->update(200, 0.0);
$seen = $touch->update(300, 0.0);
check($seen['state'] === 'CHANGE_DETECTED' && $seen['event'] === '', 'sentuhan masuk change detected');
$touch->update(400, 0.0);
$touch->update(500, 100.0);
$touch->update(600, 100.0);
$released = $touch->update(700, 100.0);
check($released['state'] === 'NORMAL' && $released['event'] === '', 'sentuhan singkat kembali normal');

$removal = new ShelfDetectorSpec(15.0, 1000);
$removal->update(0, 100.0);
$removal->update(100, 0.0);
$removal->update(200, 0.0);
$removal->update(300, 0.0);
$removal->update(400, 0.0);
$removed = $removal->update(1300, 0.0);
check($removed['event'] === 'ITEM_REMOVED' && $removed['state'] === 'NORMAL', 'pengambilan terverifikasi lalu kembali normal');
$again = $removal->update(1400, 0.0);
check($again['event'] === '', 'pengambilan tidak diulang saat berat tetap');

$removal->update(1500, 100.0);
$removal->update(1600, 100.0);
$startedReturn = $removal->update(1700, 100.0);
check($startedReturn['state'] === 'CHANGE_DETECTED' && $startedReturn['event'] === '', 'penambahan mulai diverifikasi');
$removal->update(1800, 100.0);
$returned = $removal->update(2700, 100.0);
check($returned['event'] === 'ITEM_ADDED' && $returned['state'] === 'NORMAL', 'penambahan terverifikasi lalu kembali normal');

$place = new ShelfDetectorSpec(15.0, 1000);
$place->update(0, 0.0);
$place->update(100, 100.0);
$place->update(200, 100.0);
$place->update(300, 100.0);
$place->update(400, 100.0);
$placed = $place->update(1300, 100.0);
check($placed['event'] === 'ITEM_ADDED' && $placed['state'] === 'NORMAL', 'menaruh barang tercatat lalu status normal');

$stock = new ShelfDetectorSpec(15.0, 1000);
$stock->update(0, 100.0);
foreach ([200.0, 200.0, 200.0, 200.0] as $index => $weight) {
    $stock->update(100 + ($index * 100), $weight);
}
$added = $stock->update(1300, 200.0);
check($added['event'] === 'ITEM_ADDED' && $added['state'] === 'NORMAL' && abs($added['baseline'] - 200.0) < 0.1, 'barang kedua menambah stok');
foreach ([100.0, 100.0, 100.0, 100.0] as $index => $weight) {
    $stock->update(1400 + ($index * 100), $weight);
}
$oneLeft = $stock->update(2600, 100.0);
check($oneLeft['event'] === 'ITEM_REMOVED' && $oneLeft['state'] === 'NORMAL' && abs($oneLeft['baseline'] - 100.0) < 0.1, 'sisa satu barang tetap normal');
foreach ([0.0, 0.0, 0.0, 0.0] as $index => $weight) {
    $stock->update(2700 + ($index * 100), $weight);
}
$empty = $stock->update(3900, 0.0);
check($empty['event'] === 'ITEM_REMOVED' && $empty['state'] === 'NORMAL', 'pengambilan berikutnya tetap tercatat');

$tare = new ShelfDetectorSpec(15.0, 1000);
$tare->update(0, 180.0);
$tare->resetBaseline();
$afterTare = $tare->update(100, 0.0);
check($afterTare['event'] === '' && $afterTare['state'] === 'NORMAL' && abs($afterTare['baseline']) < 0.1, 'tare mengosongkan dasar tanpa event');

$queue = new EventQueueSpec();
check($queue->enqueue('evt-000001') === true, 'event masuk antrean');
check($queue->enqueue('evt-000001') === false, 'event_id yang sama ditolak');
$queue->markHeadAttempt();
check($queue->headId() === 'evt-000001' && $queue->size() === 1, 'retry mempertahankan event_id');
for ($i = 2; $i <= EventQueueSpec::CAPACITY; $i++) {
    check($queue->enqueue(sprintf('evt-%06d', $i)) === true, 'antrean terisi');
}
check($queue->enqueue('evt-999999') === false, 'antrean penuh menolak event baru');
check($queue->headId() === 'evt-000001', 'event lama tetap di depan');
$queue->popHead();
check($queue->headId() === 'evt-000002', 'ack menghapus kepala antrean');

fwrite(STDOUT, "semua uji deteksi dan antrean lulus\n");
