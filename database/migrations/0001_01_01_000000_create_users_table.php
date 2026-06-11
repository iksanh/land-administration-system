<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Domain `users` table ported from the FastAPI/Alembic schema.
 * UUID PK, bcrypt hash stored in `hashed_password`, role + is_active,
 * created_at only (no updated_at). The default Laravel password_reset_tokens
 * and sessions tables are intentionally dropped (session driver is file).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('hashed_password', 255);
            $table->string('role', 50)->default('petugas');
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
