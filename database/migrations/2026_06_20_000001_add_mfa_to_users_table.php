<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds optional, per-user TOTP multi-factor auth to `users`.
 * Secret and recovery codes are stored via the model's `encrypted` casts
 * (so they are ciphertext at rest) — hence `text` columns, not fixed-length.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('mfa_secret')->nullable()->after('hashed_password');
            $table->boolean('mfa_enabled')->default(false)->after('mfa_secret');
            $table->timestamp('mfa_confirmed_at')->nullable()->after('mfa_enabled');
            $table->text('mfa_recovery_codes')->nullable()->after('mfa_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mfa_secret', 'mfa_enabled', 'mfa_confirmed_at', 'mfa_recovery_codes']);
        });
    }
};
