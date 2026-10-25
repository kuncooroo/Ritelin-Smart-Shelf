<?php

namespace App\Livewire;

use App\Livewire\Concerns\AuthorizesShelf;
use App\Livewire\Concerns\ConfirmsActions;
use App\Livewire\Concerns\RefreshesWithDashboard;
use App\Livewire\Concerns\WithPageSize;
use App\Models\PosTransaction;
use App\Models\ShelfEvent;
use App\Services\AnomalySync;
use App\Services\AuditLogger;
use App\Services\ShelfMatcher;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['title' => 'Rekonsiliasi'])]
class Reconciliation extends Component
{
    use AuthorizesShelf;
    use ConfirmsActions;
    use RefreshesWithDashboard;
    use WithPageSize;
    use WithPagination;

    public ?int $noteEventId = null;

    public string $staffNote = '';

    public string $message = '';

    public function mount(ShelfMatcher $matcher, AnomalySync $anomalies): void
    {
        $matcher->reconcileAll();
        $anomalies->sync();
    }

    public function editNote(int $eventId): void
    {
        $event = ShelfEvent::query()->findOrFail($eventId);
        if ($event->checked_at === null) {
            $event->checked_at = now();
            $event->save();
        }
        $this->noteEventId = $event->id;
        $this->staffNote = (string) $event->staff_note;
        $this->resetValidation();
    }

    public function closeNote(): void
    {
        $this->noteEventId = null;
        $this->staffNote = '';
        $this->resetValidation();
    }

    public function saveNote(AuditLogger $audit): void
    {
        $this->authorizeShelf(\App\Enums\PermissionName::ReconciliationView);
        $this->validate([
            'noteEventId' => ['required', 'exists:shelf_events,id'],
            'staffNote' => ['nullable', 'string', 'max:500'],
        ]);

        $event = ShelfEvent::query()->findOrFail($this->noteEventId);
        $event->staff_note = $this->staffNote !== '' ? $this->staffNote : null;
        $event->save();
        $audit->record('reconciliation.note', 'Catatan verifikasi untuk '.$event->event_id.' disimpan.', $event);
        $this->message = 'Catatan verifikasi disimpan.';
        $this->closeNote();
    }

    public function render(): View
    {
        return view('livewire.reconciliation', [
            'matched' => $this->group('matched', 'matchedPage'),
            'review' => $this->group('needs_review', 'reviewPage'),
            'unmatched' => $this->group('unmatched', 'unmatchedPage'),
            'pending' => $this->group('pending', 'pendingPage'),
            'transactions' => PosTransaction::query()
                ->with(['product', 'shelfEvent.device'])
                ->whereDoesntHave('shelfEvent')
                ->orderByDesc('occurred_at')
                ->paginate($this->pageSize(), ['*'], 'posPage'),
            'noteEvent' => $this->noteEventId === null ? null : ShelfEvent::query()->find($this->noteEventId),
        ]);
    }

    /**
     * @return list<string>
     */
    protected function confirmableActions(): array
    {
        return ['saveNote'];
    }

    protected function pageNames(): array
    {
        return ['matchedPage', 'reviewPage', 'unmatchedPage', 'pendingPage', 'posPage'];
    }

    private function group(string $status, string $pageName): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return ShelfEvent::query()
            ->with(['device.product', 'transaction'])
            ->where('event_type', 'ITEM_REMOVED')
            ->where('match_status', $status)
            ->orderByDesc('occurred_at')
            ->paginate($this->pageSize(), ['*'], $pageName);
    }
}
