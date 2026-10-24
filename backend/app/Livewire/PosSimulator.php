<?php

namespace App\Livewire;

use App\Enums\PermissionName;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Services\ShelfMatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Transaksi kasir'])]
class PosSimulator extends Component
{
    public string $productId = '';

    public int $quantity = 1;

    public string $occurredAt = '';

    #[Url]
    public string $search = '';

    public bool $showRecord = false;

    public function mount(ShelfMatcher $matcher): void
    {
        $this->occurredAt = now()->timezone('Asia/Jakarta')->format('Y-m-d\TH:i');
        $matcher->reconcileAll();
    }

    public function openRecord(): void
    {
        $this->resetValidation();
        $this->showRecord = true;
    }

    public function closeRecord(): void
    {
        $this->showRecord = false;
    }

    public function record(ShelfMatcher $matcher): void
    {
        abort_unless(auth()->user()?->hasPermission(PermissionName::PosManage) ?? false, 403);
        $this->validate([
            'productId' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'occurredAt' => ['required', 'date'],
        ]);

        PosTransaction::query()->create([
            'external_id' => 'sim-'.Str::lower((string) Str::ulid()),
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
            'occurred_at' => Carbon::parse($this->occurredAt, 'Asia/Jakarta')->utc(),
            'source' => 'simulator',
        ]);

        $matcher->reconcileAll();
        $this->quantity = 1;
        $this->showRecord = false;
    }

    public function render(): View
    {
        $transactions = PosTransaction::query()->with(['product', 'shelfEvent.device']);

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $transactions->where(function ($query) use ($term): void {
                $query->where('external_id', 'like', $term)
                    ->orWhereHas('product', function ($product) use ($term): void {
                        $product->where('sku', 'like', $term)->orWhere('name', 'like', $term);
                    })
                    ->orWhereHas('shelfEvent', fn ($event) => $event->where('event_id', 'like', $term));
            });
        }

        return view('livewire.pos-simulator', [
            'products' => Product::query()->where('is_active', true)->orderBy('name')->get(),
            'transactions' => $transactions->orderByDesc('occurred_at')->limit(30)->get(),
            'posReady' => config('ritelin.pos_token') !== null && config('ritelin.pos_token') !== '',
        ]);
    }
}
