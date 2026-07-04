<?php

namespace App\Livewire\Auth;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Step 1 of the reset flow: request a reset link by email.
 *
 * The response is intentionally identical whether or not the email belongs to
 * an active account, so the form can't be used to enumerate users. A token is
 * generated, stored hashed in `password_reset_tokens`, and emailed as a link
 * to App\Livewire\Auth\ResetPassword.
 */
#[Layout('components.layouts.guest')]
class ForgotPassword extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    /** Set after a successful submit to swap the form for a confirmation notice. */
    public bool $sent = false;

    public function sendResetLink(): void
    {
        $this->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Masukkan alamat email Anda',
            'email.email' => 'Format email tidak valid',
        ]);

        // Throttle per email + IP to limit abuse / mail spam.
        $key = 'reset-link:'.Str::lower($this->email).'|'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak permintaan. Coba lagi dalam beberapa menit.',
            ]);
        }
        RateLimiter::hit($key, 600);

        $user = User::where('email', $this->email)->first();

        // Only act for active accounts, but never reveal whether one exists.
        if ($user && $user->is_active) {
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($token), 'created_at' => Carbon::now()],
            );

            $resetUrl = route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ]);

            Mail::to($user->email)->send(new ResetPasswordMail(
                name: $user->name,
                resetUrl: $resetUrl,
                expireMinutes: (int) config('auth.passwords.users.expire', 60),
            ));
        }

        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
