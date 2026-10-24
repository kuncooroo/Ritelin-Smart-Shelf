<?php

namespace App\Livewire;

use App\Livewire\Concerns\AuthorizesShelf;
use App\Models\PosTransaction;
use App\Models\ShelfEvent;
use App\Services\AnomalySync;
use App\Services\AuditLogger;
use App\Services\ShelfMatcher;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Rekonsiliasi'])]
class Reconciliation extends Component
{
    use AuthorizesShelf;

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
        $events = ShelfEvent::query()
            ->with(['device.product', 'transaction'])
            ->where('event_type', 'ITEM_REMOVED')
            ->orderByDesc('occurred_at')
            ->limit(80)
            ->get();

        return view('livewire.reconciliation', [
            'matched' => $events->where('match_status', 'matched')->values(),
            'review' => $events->where('match_status', 'needs_review')->values(),
            'unmatched' => $events->where('match_status', 'unmatched')->values(),
            'pending' => $events->where('match_status', 'pending')->values(),
            'transactions' => PosTransaction::query()
                ->with(['product', 'shelfEvent.device'])
                ->whereDoesntHave('shelfEvent')
                ->orderByDesc('occurred_at')
                ->limit(40)
                ->get(),
            'noteEvent' => $this->noteEventId === null ? null : ShelfEvent::query()->find($this->noteEventId),
        ]);
    }
}
