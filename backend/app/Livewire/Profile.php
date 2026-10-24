<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Profil'])]
class Profile extends Component
{
    public string $name = '';

    public string $currentPassword = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public string $message = '';

    public function mount(): void
    {
        $this->name = (string) auth()->user()?->name;
    }

    public function save(AuditLogger $audit): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'currentPassword' => ['required_with:password', 'current_password'],
            'password' => ['nullable', 'same:passwordConfirmation', Password::min(8)],
        ]);

        /** @var User $user */
        $user = auth()->user();
        $user->name = $this->name;
        if ($this->password !== '') {
            $user->password = $this->password;
        }
        $user->save();
        $audit->record('profile.updated', 'Profil '.$user->email.' diperbarui.', $user);
        $this->reset('currentPassword', 'password', 'passwordConfirmation');
        $this->message = 'Profil disimpan.';
    }

    public function render(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $user->loadMissing('roles');

        return view('livewire.profile', [
            'account' => $user,
        ]);
    }
}
