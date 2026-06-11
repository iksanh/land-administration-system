<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Petugas Satu',
            'email' => 'petugas@app.com',
            'hashed_password' => Hash::make('secret123'),
            'role' => 'petugas',
            'is_active' => true,
        ], $overrides));
    }

    public function test_login_page_is_reachable_by_guests(): void
    {
        $this->get('/login')->assertOk()->assertSeeLivewire(Login::class);
    }

    public function test_valid_credentials_log_the_user_in_and_redirect(): void
    {
        $this->makeUser(['email' => 'login@app.com', 'hashed_password' => Hash::make('rahasia12')]);

        Livewire::test(Login::class)
            ->set('email', 'login@app.com')
            ->set('password', 'rahasia12')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->makeUser(['email' => 'login2@app.com', 'hashed_password' => Hash::make('rahasia12')]);

        Livewire::test(Login::class)
            ->set('email', 'login2@app.com')
            ->set('password', 'salah')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->makeUser(['email' => 'inactive@app.com', 'hashed_password' => Hash::make('rahasia12'), 'is_active' => false]);

        Livewire::test(Login::class)
            ->set('email', 'inactive@app.com')
            ->set('password', 'rahasia12')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_validation_requires_email_and_password(): void
    {
        Livewire::test(Login::class)
            ->set('email', 'not-an-email')
            ->set('password', '')
            ->call('login')
            ->assertHasErrors(['email', 'password']);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee($user->name);
    }

    public function test_logout_ends_the_session(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    // ---- role middleware (ports FastAPI get_current_admin) ----

    public function test_admin_route_forbidden_for_petugas(): void
    {
        Route::middleware(['auth', 'role:admin'])->get('/_test/admin-only', fn () => 'ok');

        $petugas = $this->makeUser(['email' => 'p2@app.com', 'role' => 'petugas']);

        $this->actingAs($petugas)->get('/_test/admin-only')->assertStatus(403);
    }

    public function test_admin_route_allowed_for_admin(): void
    {
        Route::middleware(['auth', 'role:admin'])->get('/_test/admin-only', fn () => 'ok');

        $admin = $this->makeUser(['email' => 'a2@app.com', 'role' => 'admin']);

        $this->actingAs($admin)->get('/_test/admin-only')->assertOk()->assertSee('ok');
    }
}
