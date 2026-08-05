<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('petugas')->after('email');
            $table->string('nip')->nullable()->after('role');
            $table->string('no_hp')->nullable()->after('nip');
            $table->string('no_wa')->nullable()->after('no_hp');
            $table->boolean('is_banned')->default(false)->after('no_wa');
            $table->timestamp('tgl_daftar')->nullable()->after('is_banned');
        });

        DB::table('users')->update(['tgl_daftar' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'nip', 'no_hp', 'no_wa', 'is_banned', 'tgl_daftar']);
        });
    }
};
