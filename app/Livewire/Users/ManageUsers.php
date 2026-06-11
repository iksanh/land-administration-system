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

    public function toggleActive(string $id): void
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => ! $user->is_active]);
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
