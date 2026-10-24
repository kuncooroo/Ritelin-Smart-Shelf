<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Services\AnomalySync;
use App\Services\ShelfMatcher;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosTransactionController extends Controller
{
    public function store(Request $request, ShelfMatcher $matcher, AnomalySync $anomalies): JsonResponse
    {
        $data = $request->validate([
            'external_id' => ['required', 'string', 'max:64'],
            'sku' => ['required', 'string', 'exists:products,sku'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'timestamp' => ['required', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/'],
        ]);

        $product = Product::query()->where('sku', $data['sku'])->firstOrFail();
        $existing = PosTransaction::query()->where('external_id', $data['external_id'])->first();

        if ($existing !== null) {
            $matcher->reconcileAll();
            $anomalies->sync();

            return $this->payload($existing, true, 200);
        }

        try {
            $transaction = PosTransaction::query()->create([
                'external_id' => $data['external_id'],
                'product_id' => $product->id,
                'quantity' => $data['quantity'],
                'occurred_at' => $data['timestamp'],
                'source' => 'webhook',
            ]);
        } catch (UniqueConstraintViolationException) {
            $transaction = PosTransaction::query()->where('external_id', $data['external_id'])->firstOrFail();
            $matcher->reconcileAll();
            $anomalies->sync();

            return $this->payload($transaction, true, 200);
        }

        $matcher->reconcileAll();
        $anomalies->sync();

        return $this->payload($transaction, false, 201);
    }

    private function payload(PosTransaction $transaction, bool $duplicate, int $status): JsonResponse
    {
        $transaction->loadMissing('product');

        return response()->json([
            'duplicate' => $duplicate,
            'data' => [
                'external_id' => $transaction->external_id,
                'sku' => $transaction->product->sku,
                'quantity' => $transaction->quantity,
                'timestamp' => $transaction->occurred_at->utc()->format('Y-m-d\TH:i:s\Z'),
            ],
        ], $status);
    }
}
