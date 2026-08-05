<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->boolean('is_active')->default(true)->after('is_banned');
            $table->foreignId('created_by')->nullable()->after('tgl_daftar')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('last_login_at')->nullable()->after('updated_by');
            $table->timestamp('deactivated_at')->nullable()->after('last_login_at');
            $table->foreignId('deactivated_by')->nullable()->after('deactivated_at')->constrained('users')->nullOnDelete();
        });

        // is_active adalah kebalikan semantik is_banned yang sudah ada.
        DB::table('users')->update(['is_active' => DB::raw('NOT is_banned')]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_banned');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_banned')->default(false);
        });

        DB::table('users')->update(['is_banned' => DB::raw('NOT is_active')]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deactivated_by');
            $table->dropColumn(['username', 'is_active', 'created_by', 'updated_by', 'last_login_at', 'deactivated_at']);
        });
    }
};
