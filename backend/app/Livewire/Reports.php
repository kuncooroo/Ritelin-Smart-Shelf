<?php

namespace App\Livewire;

use App\Services\ShelfReport;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Laporan'])]
class Reports extends Component
{
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
    }

    public function render(ShelfReport $report): View
    {
        [$start, $end] = $report->range($this->from, $this->to);

        return view('livewire.reports', [
            'report' => $report->build($start, $end),
        ]);
    }
}
