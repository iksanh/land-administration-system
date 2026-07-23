<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Satu user bisa memegang beberapa role (admin/petugas/koorsub):
 * kolom string `role` diganti `roles` (JSON array of string).
 * Data lama dipindahkan apa adanya: role tunggal menjadi array satu elemen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('roles')->nullable()->after('role');
        });

        foreach (DB::table('users')->select('id', 'role')->get() as $u) {
            DB::table('users')->where('id', $u->id)
                ->update(['roles' => json_encode([$u->role ?: 'petugas'])]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('petugas')->after('roles');
        });

        foreach (DB::table('users')->select('id', 'roles')->get() as $u) {
            $roles = json_decode($u->roles ?? '[]', true) ?: ['petugas'];
            DB::table('users')->where('id', $u->id)->update(['role' => $roles[0]]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('roles');
        });
    }
};
