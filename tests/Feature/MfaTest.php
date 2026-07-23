<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\ManageMfa;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class MfaTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Petugas Satu',
            'email' => 'petugas@app.com',
            'hashed_password' => Hash::make('rahasia12'),
            'roles' => ['petugas'],
            'is_active' => true,
        ], $overrides));
    }

    /** MFA fields aren't mass-assignable (by design), so set them directly. */
    private function makeMfaUser(string $email, string $secret, array $recovery = []): User
    {
        $user = $this->makeUser(['email' => $email]);
        $user->mfa_secret = $secret;
        $user->mfa_enabled = true;
        $user->mfa_confirmed_at = now();
        $user->mfa_recovery_codes = $recovery ?: null;
        $user->save();

        return $user;
    }

    public function test_totp_generate_and_verify_roundtrip(): void
    {
        $secret = Totp::generateSecret();
        $code = Totp::code($secret);

        $this->assertSame(6, strlen($code));
        $this->assertTrue(Totp::verify($secret, $code));
        $this->assertFalse(Totp::verify($secret, '000000', window: 0, timestamp: time() + 10_000));
    }

    public function test_user_without_mfa_logs_in_directly(): void
    {
        $this->makeUser(['email' => 'plain@app.com']);

        Livewire::test(Login::class)
            ->set('email', 'plain@app.com')
            ->set('password', 'rahasia12')
            ->call('login')
            ->assertSet('awaitingMfa', false)
            ->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_mfa_user_is_challenged_then_logged_in_with_valid_code(): void
    {
        $secret = Totp::generateSecret();
        $this->makeMfaUser('mfa@app.com', $secret);

        $component = Livewire::test(Login::class)
            ->set('email', 'mfa@app.com')
            ->set('password', 'rahasia12')
            ->call('login')
            ->assertSet('awaitingMfa', true)
            ->assertNoRedirect();

        $this->assertGuest();

        $component->set('otp', Totp::code($secret))
            ->call('verifyOtp')
            ->assertHasNoErrors()
            ->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_mfa_user_with_wrong_code_is_rejected(): void
    {
        $secret = Totp::generateSecret();
        $this->makeMfaUser('mfa2@app.com', $secret);

        Livewire::test(Login::class)
            ->set('email', 'mfa2@app.com')
            ->set('password', 'rahasia12')
            ->call('login')
            ->set('otp', '123456')
            ->call('verifyOtp')
            ->assertHasErrors('otp');

        $this->assertGuest();
    }

    public function test_recovery_code_logs_in_and_is_consumed(): void
    {
        $secret = Totp::generateSecret();
        $user = $this->makeMfaUser('rec@app.com', $secret, ['AAAAABBBBB', 'CCCCCDDDDD']);

        Livewire::test(Login::class)
            ->set('email', 'rec@app.com')
            ->set('password', 'rahasia12')
            ->call('login')
            ->set('otp', 'AAAAA-BBBBB') // separators ignored
            ->call('verifyOtp')
            ->assertRedirect('/dashboard');

        $this->assertAuthenticated();
        $this->assertSame(['CCCCCDDDDD'], $user->fresh()->mfa_recovery_codes);
    }

    public function test_user_can_enable_mfa(): void
    {
        $user = $this->makeUser(['email' => 'enable@app.com']);

        $component = Livewire::actingAs($user)->test(ManageMfa::class)
            ->call('startSetup')
            ->assertSet('settingUp', true);

        $secret = session('mfa.setup_secret');
        $this->assertNotEmpty($secret);

        $component->set('confirmCode', Totp::code($secret))
            ->call('confirmSetup')
            ->assertHasNoErrors();

        $fresh = $user->fresh();
        $this->assertTrue($fresh->hasMfaEnabled());
        $this->assertSame($secret, $fresh->mfa_secret);
        $this->assertCount(8, $fresh->mfa_recovery_codes);
    }

    public function test_enable_rejects_wrong_confirmation_code(): void
    {
        $user = $this->makeUser(['email' => 'enable2@app.com']);

        Livewire::actingAs($user)->test(ManageMfa::class)
            ->call('startSetup')
            ->set('confirmCode', '000000')
            ->call('confirmSetup')
            ->assertHasErrors('confirmCode');

        $this->assertFalse($user->fresh()->hasMfaEnabled());
    }

    public function test_user_can_disable_mfa_with_valid_code(): void
    {
        $secret = Totp::generateSecret();
        $user = $this->makeMfaUser('disable@app.com', $secret, ['AAAAABBBBB']);

        Livewire::actingAs($user)->test(ManageMfa::class)
            ->set('disableCode', Totp::code($secret))
            ->call('disable')
            ->assertHasNoErrors();

        $this->assertFalse($user->fresh()->hasMfaEnabled());
        $this->assertNull($user->fresh()->mfa_secret);
    }
}
