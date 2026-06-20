<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Support\Totp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.guest')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    /** Second factor: shown only after a valid password when the user has MFA. */
    public bool $awaitingMfa = false;

    public string $otp = '';

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Validate credentials WITHOUT logging in, so MFA can gate the session.
        // Uses the same provider/hasher as Auth::attempt (passlib $2b$ hashes ok).
        if (! Auth::validate(['email' => $this->email, 'password' => $this->password])) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah',
            ]);
        }

        $user = User::where('email', $this->email)->first();

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Akun tidak aktif',
            ]);
        }

        if ($user->hasMfaEnabled()) {
            // Park the verified identity in the session; finish in verifyOtp().
            session(['login.mfa_user_id' => $user->id]);
            $this->reset('password');
            $this->awaitingMfa = true;

            return;
        }

        return $this->completeLogin($user);
    }

    public function verifyOtp()
    {
        $this->validate(['otp' => 'required|string'], [
            'otp.required' => 'Masukkan kode autentikasi',
        ]);

        $userId = session('login.mfa_user_id');
        $user = $userId ? User::find($userId) : null;

        if (! $user || ! $user->hasMfaEnabled()) {
            // Session expired or tampered — restart the flow.
            $this->reset(['awaitingMfa', 'otp', 'password']);
            session()->forget('login.mfa_user_id');
            throw ValidationException::withMessages([
                'otp' => 'Sesi login berakhir, silakan masuk kembali',
            ]);
        }

        $code = trim($this->otp);

        if (Totp::verify($user->mfa_secret, $code) || $this->consumeRecoveryCode($user, $code)) {
            return $this->completeLogin($user);
        }

        throw ValidationException::withMessages([
            'otp' => 'Kode autentikasi salah',
        ]);
    }

    /** Match a recovery code (case-insensitive, separators ignored), burn on success. */
    private function consumeRecoveryCode(User $user, string $code): bool
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
        if ($code === '') {
            return false;
        }
        $codes = $user->mfa_recovery_codes ?? [];

        foreach ($codes as $i => $stored) {
            if (hash_equals(strtoupper($stored), $code)) {
                unset($codes[$i]);
                $user->mfa_recovery_codes = array_values($codes);
                $user->save();

                return true;
            }
        }

        return false;
    }

    private function completeLogin(User $user)
    {
        Auth::login($user);
        session()->forget('login.mfa_user_id');
        session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
