<?php

namespace Tests\Feature;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordResetTest extends TestCase
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

    /** Capture the plaintext token from the queued/sent mailable's URL. */
    private function tokenFromSentMail(): string
    {
        $url = null;
        Mail::assertSent(ResetPasswordMail::class, function (ResetPasswordMail $mail) use (&$url) {
            $url = $mail->resetUrl;

            return true;
        });

        parse_str(parse_url($url, PHP_URL_QUERY), $query);

        return $query['token'];
    }

    public function test_forgot_password_page_is_reachable_by_guests(): void
    {
        $this->get('/lupa-password')->assertOk()->assertSeeLivewire(ForgotPassword::class);
    }

    public function test_request_sends_a_reset_link_for_an_active_user(): void
    {
        Mail::fake();
        $this->makeUser(['email' => 'reset@app.com']);

        Livewire::test(ForgotPassword::class)
            ->set('email', 'reset@app.com')
            ->call('sendResetLink')
            ->assertHasNoErrors()
            ->assertSet('sent', true);

        Mail::assertSent(ResetPasswordMail::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'reset@app.com']);
    }

    public function test_request_for_unknown_email_does_not_leak_and_sends_nothing(): void
    {
        Mail::fake();

        Livewire::test(ForgotPassword::class)
            ->set('email', 'nobody@app.com')
            ->call('sendResetLink')
            ->assertHasNoErrors()
            ->assertSet('sent', true);

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'nobody@app.com']);
    }

    public function test_inactive_user_does_not_receive_a_link(): void
    {
        Mail::fake();
        $this->makeUser(['email' => 'off@app.com', 'is_active' => false]);

        Livewire::test(ForgotPassword::class)
            ->set('email', 'off@app.com')
            ->call('sendResetLink')
            ->assertSet('sent', true);

        Mail::assertNothingSent();
    }

    public function test_requests_are_throttled(): void
    {
        Mail::fake();
        RateLimiter::clear('reset-link:throttle@app.com|127.0.0.1');
        $this->makeUser(['email' => 'throttle@app.com']);

        for ($i = 0; $i < 3; $i++) {
            Livewire::test(ForgotPassword::class)
                ->set('email', 'throttle@app.com')
                ->call('sendResetLink')
                ->assertHasNoErrors();
        }

        Livewire::test(ForgotPassword::class)
            ->set('email', 'throttle@app.com')
            ->call('sendResetLink')
            ->assertHasErrors('email');
    }

    public function test_valid_token_resets_the_password(): void
    {
        Mail::fake();
        $user = $this->makeUser(['email' => 'change@app.com']);

        Livewire::test(ForgotPassword::class)
            ->set('email', 'change@app.com')
            ->call('sendResetLink');

        $token = $this->tokenFromSentMail();

        Livewire::test(ResetPassword::class)
            ->set('token', $token)
            ->set('email', 'change@app.com')
            ->set('password', 'newpassword12')
            ->set('password_confirmation', 'newpassword12')
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertRedirect('/login');

        $this->assertTrue(Hash::check('newpassword12', $user->fresh()->hashed_password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'change@app.com']);
    }

    public function test_reset_then_login_with_new_password(): void
    {
        Mail::fake();
        $this->makeUser(['email' => 'flow@app.com']);

        Livewire::test(ForgotPassword::class)
            ->set('email', 'flow@app.com')
            ->call('sendResetLink');

        $token = $this->tokenFromSentMail();

        Livewire::test(ResetPassword::class)
            ->set('token', $token)
            ->set('email', 'flow@app.com')
            ->set('password', 'brandnew123')
            ->set('password_confirmation', 'brandnew123')
            ->call('resetPassword');

        Livewire::test(Login::class)
            ->set('email', 'flow@app.com')
            ->set('password', 'brandnew123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_invalid_token_is_rejected(): void
    {
        Mail::fake();
        $user = $this->makeUser(['email' => 'bad@app.com']);

        Livewire::test(ForgotPassword::class)
            ->set('email', 'bad@app.com')
            ->call('sendResetLink');

        Livewire::test(ResetPassword::class)
            ->set('token', 'totally-wrong-token')
            ->set('email', 'bad@app.com')
            ->set('password', 'newpassword12')
            ->set('password_confirmation', 'newpassword12')
            ->call('resetPassword')
            ->assertHasErrors('email');

        $this->assertTrue(Hash::check('secret123', $user->fresh()->hashed_password));
    }

    public function test_expired_token_is_rejected(): void
    {
        Mail::fake();
        $user = $this->makeUser(['email' => 'old@app.com']);

        Livewire::test(ForgotPassword::class)
            ->set('email', 'old@app.com')
            ->call('sendResetLink');

        $token = $this->tokenFromSentMail();

        // Age the token past the configured expiry window.
        $expire = (int) config('auth.passwords.users.expire', 60);
        DB::table('password_reset_tokens')
            ->where('email', 'old@app.com')
            ->update(['created_at' => Carbon::now()->subMinutes($expire + 5)]);

        Livewire::test(ResetPassword::class)
            ->set('token', $token)
            ->set('email', 'old@app.com')
            ->set('password', 'newpassword12')
            ->set('password_confirmation', 'newpassword12')
            ->call('resetPassword')
            ->assertHasErrors('email');

        $this->assertTrue(Hash::check('secret123', $user->fresh()->hashed_password));
    }

    public function test_password_must_be_confirmed_and_long_enough(): void
    {
        Livewire::test(ResetPassword::class)
            ->set('token', 'x')
            ->set('email', 'someone@app.com')
            ->set('password', 'short')
            ->set('password_confirmation', 'mismatch')
            ->call('resetPassword')
            ->assertHasErrors('password');
    }
}
