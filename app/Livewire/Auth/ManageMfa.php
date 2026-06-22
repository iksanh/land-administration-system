<?php

namespace App\Livewire\Auth;

use App\Support\Totp;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Per-user, opt-in TOTP management. Enrollment: generate a secret (kept in the
 * session, not the DB, until confirmed) → user scans the QR → confirms a code →
 * we persist the secret, flip mfa_enabled, and show one-time recovery codes.
 */
#[Layout('components.layouts.app')]
class ManageMfa extends Component
{
    // Enrollment state
    public bool $settingUp = false;

    public string $otpauthUri = '';

    public string $manualKey = '';

    public string $confirmCode = '';

    // Shown once, right after enabling or regenerating
    public array $recoveryCodes = [];

    // Disable form
    public string $disableCode = '';

    // Self-service password change
    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public function startSetup(): void
    {
        if (Auth::user()->hasMfaEnabled()) {
            return;
        }

        $secret = Totp::generateSecret();
        session(['mfa.setup_secret' => $secret]);

        $this->manualKey = $secret;
        $this->otpauthUri = Totp::otpauthUri(
            $secret,
            Auth::user()->email,
            config('app.name', 'Laravel'),
        );
        $this->confirmCode = '';
        $this->recoveryCodes = [];
        $this->settingUp = true;
    }

    public function cancelSetup(): void
    {
        session()->forget('mfa.setup_secret');
        $this->reset(['settingUp', 'otpauthUri', 'manualKey', 'confirmCode']);
    }

    public function confirmSetup(): void
    {
        $this->validate(['confirmCode' => 'required|string'], [
            'confirmCode.required' => 'Masukkan kode dari aplikasi autentikator',
        ]);

        $secret = session('mfa.setup_secret');

        if (! $secret || ! Totp::verify($secret, trim($this->confirmCode))) {
            throw ValidationException::withMessages([
                'confirmCode' => 'Kode salah. Coba kode terbaru dari aplikasi.',
            ]);
        }

        $codes = $this->freshRecoveryCodes();

        $user = Auth::user();
        $user->mfa_secret = $secret;
        $user->mfa_enabled = true;
        $user->mfa_confirmed_at = now();
        $user->mfa_recovery_codes = $codes;
        $user->save();

        session()->forget('mfa.setup_secret');

        $this->reset(['settingUp', 'otpauthUri', 'manualKey', 'confirmCode']);
        $this->recoveryCodes = $codes;
        session()->flash('message', 'Autentikasi dua faktor berhasil diaktifkan.');
    }

    public function regenerateRecoveryCodes(): void
    {
        $user = Auth::user();

        if (! $user->hasMfaEnabled()) {
            return;
        }

        $codes = $this->freshRecoveryCodes();
        $user->mfa_recovery_codes = $codes;
        $user->save();

        $this->recoveryCodes = $codes;
        session()->flash('message', 'Kode pemulihan baru dibuat. Kode lama tidak berlaku lagi.');
    }

    public function disable(): void
    {
        $user = Auth::user();

        if (! $user->hasMfaEnabled()) {
            return;
        }

        $this->validate(['disableCode' => 'required|string'], [
            'disableCode.required' => 'Masukkan kode untuk menonaktifkan',
        ]);

        $code = trim($this->disableCode);
        $isRecovery = collect($user->mfa_recovery_codes ?? [])
            ->contains(fn ($c) => hash_equals(
                strtoupper($c),
                strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code))
            ));

        if (! Totp::verify($user->mfa_secret, $code) && ! $isRecovery) {
            throw ValidationException::withMessages([
                'disableCode' => 'Kode salah, MFA tidak dinonaktifkan.',
            ]);
        }

        $user->mfa_secret = null;
        $user->mfa_enabled = false;
        $user->mfa_confirmed_at = null;
        $user->mfa_recovery_codes = null;
        $user->save();

        $this->reset(['disableCode', 'recoveryCodes']);
        session()->flash('message', 'Autentikasi dua faktor dinonaktifkan.');
    }

    public function changePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:6'],
            'newPasswordConfirmation' => ['required', 'same:newPassword'],
        ], [
            'newPasswordConfirmation.same' => 'Konfirmasi password tidak cocok.',
        ], [
            'currentPassword' => 'password saat ini',
            'newPassword' => 'password baru',
            'newPasswordConfirmation' => 'konfirmasi password',
        ]);

        $user = Auth::user();

        if (! Hash::check($this->currentPassword, $user->getAuthPassword())) {
            throw ValidationException::withMessages([
                'currentPassword' => 'Password saat ini salah.',
            ]);
        }

        $user->hashed_password = Hash::make($this->newPassword);
        $user->save();

        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        session()->flash('message', 'Password berhasil diperbarui.');
    }

    /** Eight normalized (uppercase, no separators) one-time recovery codes. */
    private function freshRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn () => strtoupper(bin2hex(random_bytes(5)))) // 10 hex chars each
            ->all();
    }

    public function render()
    {
        return view('livewire.auth.manage-mfa', [
            'enabled' => Auth::user()->hasMfaEnabled(),
        ]);
    }
}
