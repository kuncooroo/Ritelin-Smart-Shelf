<?php

namespace App\Livewire\Concerns;

trait WithPageSize
{
    public int $perPage = 10;

    public function updatingPerPage(): void
    {
        foreach ($this->pageNames() as $name) {
            $this->resetPage($name);
        }
    }

    public function updatedPerPage(mixed $value): void
    {
        $this->perPage = in_array((int) $value, [10, 25, 50], true) ? (int) $value : 10;
    }

    protected function pageSize(): int
    {
        return in_array($this->perPage, [10, 25, 50], true) ? $this->perPage : 10;
    }

    /**
     * @return list<string>
     */
    protected function pageNames(): array
    {
        return ['page'];
    }
}
