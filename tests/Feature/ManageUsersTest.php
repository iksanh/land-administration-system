<?php

namespace Tests\Feature;

use App\Livewire\Users\ManageUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ManageUsersTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@app.com',
            'hashed_password' => Hash::make('admin123'), 'roles' => ['admin'], 'is_active' => true,
        ]);
    }

    public function test_users_page_is_admin_only(): void
    {
        $petugas = User::create([
            'name' => 'P', 'email' => 'p@app.com',
            'hashed_password' => Hash::make('x'), 'roles' => ['petugas'], 'is_active' => true,
        ]);

        $this->actingAs($petugas)->get('/users')->assertStatus(403);
        $this->actingAs($this->admin())->get('/users')->assertOk();
    }

    public function test_admin_can_create_user_with_hashed_password(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ManageUsers::class)
            ->set('name', 'Budi')
            ->set('email', 'budi@app.com')
            ->set('password', 'rahasia12')
            ->set('roles', ['petugas', 'koorsub'])
            ->call('create')
            ->assertHasNoErrors();

        $user = User::where('email', 'budi@app.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(['petugas', 'koorsub'], $user->roles);
        $this->assertTrue($user->hasRole('koorsub'));
        $this->assertFalse($user->isAdmin());
        $this->assertTrue(Hash::check('rahasia12', $user->hashed_password));
    }

    public function test_create_requires_at_least_one_role(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ManageUsers::class)
            ->set('name', 'Budi')
            ->set('email', 'budi2@app.com')
            ->set('password', 'rahasia12')
            ->set('roles', [])
            ->call('create')
            ->assertHasErrors(['roles']);
    }

    public function test_create_rejects_duplicate_email(): void
    {
        $this->admin();

        Livewire::test(ManageUsers::class)
            ->set('name', 'X')->set('email', 'admin@app.com')->set('password', 'rahasia12')->set('roles', ['admin'])
            ->call('create')
            ->assertHasErrors(['email' => 'unique']);
    }

    public function test_create_validates_required_and_min_password(): void
    {
        Livewire::test(ManageUsers::class)
            ->set('name', '')->set('email', 'bad')->set('password', '123')
            ->call('create')
            ->assertHasErrors(['name', 'email', 'password']);
    }

    public function test_admin_can_edit_user(): void
    {
        $u = User::create([
            'name' => 'Lama', 'email' => 'edit@app.com',
            'hashed_password' => Hash::make('x'), 'roles' => ['petugas'], 'is_active' => true,
        ]);

        Livewire::test(ManageUsers::class)
            ->call('startEdit', $u->id)
            ->set('editName', 'Baru')->set('editRoles', ['admin', 'petugas'])->set('editActive', false)
            ->call('update')
            ->assertHasNoErrors();

        $u->refresh();
        $this->assertSame('Baru', $u->name);
        $this->assertSame(['admin', 'petugas'], $u->roles);
        $this->assertTrue($u->isAdmin());
        $this->assertFalse($u->is_active);
    }

    public function test_toggle_active_and_delete(): void
    {
        $u = User::create([
            'name' => 'Z', 'email' => 'z@app.com',
            'hashed_password' => Hash::make('x'), 'roles' => ['petugas'], 'is_active' => true,
        ]);

        Livewire::test(ManageUsers::class)->call('toggleActive', $u->id);
        $this->assertFalse($u->refresh()->is_active);

        Livewire::test(ManageUsers::class)->call('delete', $u->id);
        $this->assertDatabaseMissing('users', ['id' => $u->id]);
    }
}
