<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Step 2 of the reset flow: set a new password using the emailed token.
 *
 * Validates the token against the hash stored in `password_reset_tokens` and
 * its age against `auth.passwords.users.expire`. On success the new hash is
 * written to the `hashed_password` column and the token row is burned.
 */
#[Layout('components.layouts.guest')]
class ResetPassword extends Component
{
    #[Url]
    public string $token = '';

    #[Url]
    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function resetPassword()
    {
        $this->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.required' => 'Email tidak boleh kosong',
            'email.email' => 'Format email tidak valid',
            'password.required' => 'Masukkan kata sandi baru',
            'password.min' => 'Kata sandi minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $this->email)
            ->first();

        $expireMinutes = (int) config('auth.passwords.users.expire', 60);

        $isValid = $record
            && Hash::check($this->token, $record->token)
            && Carbon::parse($record->created_at)->addMinutes($expireMinutes)->isFuture();

        if (! $isValid) {
            throw ValidationException::withMessages([
                'email' => 'Tautan atur ulang tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.',
            ]);
        }

        $user = User::where('email', $this->email)->first();

        if (! $user || ! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Akun tidak ditemukan atau tidak aktif.',
            ]);
        }

        $user->hashed_password = Hash::make($this->password);
        $user->save();

        // Burn the token so the link can't be reused.
        DB::table('password_reset_tokens')->where('email', $this->email)->delete();

        session()->flash('status', 'Kata sandi berhasil diperbarui. Silakan masuk dengan kata sandi baru Anda.');

        return redirect()->route('login');
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}
