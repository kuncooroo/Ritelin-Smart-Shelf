<?php

namespace App\Livewire\Concerns;

trait ConfirmsActions
{
    public bool $confirmOpen = false;

    public string $confirmTitle = '';

    public string $confirmText = '';

    public string $confirmMethod = '';

    /** @var array<string, mixed> */
    public array $confirmParameters = [];

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function ask(string $method, string $title, string $text, array $parameters = []): void
    {
        abort_unless(in_array($method, $this->confirmableActions(), true), 403);
        $this->confirmMethod = $method;
        $this->confirmTitle = $title;
        $this->confirmText = $text;
        $this->confirmParameters = $parameters;
        $this->confirmOpen = true;
    }

    public function cancelConfirm(): void
    {
        $this->confirmOpen = false;
        $this->confirmMethod = '';
        $this->confirmText = '';
        $this->confirmParameters = [];
    }

    public function runConfirmed(): void
    {
        $method = $this->confirmMethod;
        $parameters = $this->confirmParameters;
        abort_unless(in_array($method, $this->confirmableActions(), true), 403);
        $this->cancelConfirm();
        app()->call([$this, $method], $parameters);
    }

    /**
     * @return list<string>
     */
    abstract protected function confirmableActions(): array;
}
