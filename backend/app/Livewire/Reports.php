<?php

namespace App\Livewire;

use App\Services\ShelfReport;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\Concerns\RefreshesWithDashboard;
use App\Livewire\Concerns\WithPageSize;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Laporan & Analisis'])]
class Reports extends Component
{
    use RefreshesWithDashboard;
    use WithPageSize;
    use WithPagination;
    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function mount(ShelfReport $report): void
    {
        [$start, $end] = $report->range($this->from, $this->to);
        $this->from = $start->toDateString();
        $this->to = $end->toDateString();
    }

    public function apply(ShelfReport $report): void
    {
        [$start, $end] = $report->range($this->from, $this->to);
        $this->from = $start->toDateString();
        $this->to = $end->toDateString();
        $this->resetPage('days');
        $this->resetPage('products');
        $this->resetPage('shelves');
        $this->resetPage('devices');
    }

    protected function pageNames(): array
    {
        return ['days', 'products', 'shelves', 'devices'];
    }

    public function render(ShelfReport $report): View
    {
        [$start, $end] = $report->range($this->from, $this->to);

        $built = $report->build($start, $end);

        return view('livewire.reports', [
            'report' => $built,
            'days' => $this->pages($built['days'], 'days'),
            'products' => $this->pages($built['products'], 'products'),
            'shelves' => $this->pages($built['shelves'], 'shelves'),
            'deviceRows' => $this->pages($built['devices'], 'devices'),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function pages(array $items, string $pageName): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage($pageName);
        $perPage = $this->pageSize();
        $slice = array_slice(array_values($items), max(0, ($page - 1) * $perPage), $perPage);

        return (new LengthAwarePaginator($slice, count($items), $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]))->withQueryString();
    }
}
