<?php

namespace App\Livewire\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Ports app/api/routes/user.py + crud/user.py (admin-only "Manajemen User").
 * Create hashes the password into `hashed_password`; edit updates name/role/active.
 */
#[Layout('components.layouts.app')]
class ManageUsers extends Component
{
    public string $search = '';

    // Create form
    public bool $showForm = false;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'petugas';

    // Inline edit
    public ?string $editingId = null;
    public string $editName = '';
    public string $editRole = 'petugas';
    public bool $editActive = true;

    // Inline reset password
    public ?string $resettingId = null;
    public string $resetPassword = '';
    public string $resetPasswordConfirmation = '';

    public function create(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:admin,petugas'],
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'hashed_password' => Hash::make($data['password']),
            'role' => $data['role'],
        ]);

        $this->reset(['name', 'email', 'password', 'showForm']);
        $this->role = 'petugas';
        session()->flash('message', 'User berhasil dibuat.');
    }

    public function startEdit(string $id): void
    {
        $this->cancelResetPassword();
        $user = User::findOrFail($id);
        $this->editingId = $user->id;
        $this->editName = $user->name;
        $this->editRole = $user->role;
        $this->editActive = $user->is_active;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'editName', 'editRole', 'editActive']);
    }

    public function update(): void
    {
        $data = $this->validate([
            'editName' => ['required', 'string', 'max:100'],
            'editRole' => ['required', 'in:admin,petugas'],
            'editActive' => ['boolean'],
        ]);

        User::findOrFail($this->editingId)->update([
            'name' => $data['editName'],
            'role' => $data['editRole'],
            'is_active' => $data['editActive'],
        ]);

        $this->cancelEdit();
        session()->flash('message', 'User berhasil diperbarui.');
    }

    public function startResetPassword(string $id): void
    {
        $this->cancelEdit();
        $user = User::findOrFail($id);
        $this->resettingId = $user->id;
        $this->reset(['resetPassword', 'resetPasswordConfirmation']);
    }

    public function cancelResetPassword(): void
    {
        $this->reset(['resettingId', 'resetPassword', 'resetPasswordConfirmation']);
    }

    public function saveResetPassword(): void
    {
        $data = $this->validate([
            'resetPassword' => ['required', 'string', 'min:6'],
            'resetPasswordConfirmation' => ['required', 'same:resetPassword'],
        ], [
            'resetPasswordConfirmation.same' => 'Konfirmasi password tidak cocok.',
        ], [
            'resetPassword' => 'password',
            'resetPasswordConfirmation' => 'konfirmasi password',
        ]);

        User::findOrFail($this->resettingId)->update([
            'hashed_password' => Hash::make($data['resetPassword']),
        ]);

        $this->cancelResetPassword();
        session()->flash('message', 'Password user berhasil direset.');
    }

    public function toggleActive(string $id): void
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => ! $user->is_active]);
    }

    /**
     * Admin override: clears a user's MFA enrollment without requiring their code,
     * e.g. when they lose access to their authenticator device.
     */
    public function disableMfa(string $id): void
    {
        $user = User::findOrFail($id);

        if (! $user->hasMfaEnabled()) {
            return;
        }

        $user->mfa_secret = null;
        $user->mfa_enabled = false;
        $user->mfa_confirmed_at = null;
        $user->mfa_recovery_codes = null;
        $user->save();

        session()->flash('message', 'MFA user berhasil dinonaktifkan.');
    }

    public function delete(string $id): void
    {
        User::findOrFail($id)->delete();
        session()->flash('message', 'User berhasil dihapus.');
    }

    public function render()
    {
        return view('livewire.users.manage-users', [
            'users' => User::query()
                ->when($this->search !== '', function ($q) {
                    $term = '%'.trim($this->search).'%';
                    $q->where(fn ($w) => $w->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('role', 'like', $term));
                })
                ->orderBy('name')->get(),
            // Stats stay over the full set, independent of the search filter.
            'stats' => [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'admin' => User::where('role', 'admin')->count(),
            ],
        ]);
    }
}
