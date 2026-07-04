<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Restores the password reset token store that the original users migration
 * intentionally dropped. Backs the custom forgot/reset-password flow
 * (App\Livewire\Auth\ForgotPassword + ResetPassword). The token is stored
 * hashed (Hash::make); the plaintext only ever lives in the emailed link.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email', 100)->primary();
            $table->string('token', 255);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
