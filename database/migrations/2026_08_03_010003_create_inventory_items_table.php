<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subbuilding_id')->constrained('subbuildings')->restrictOnDelete();
            $table->foreignId('item_type_id')->constrained('item_types')->restrictOnDelete();
            $table->string('brand', 100);
            $table->text('specification');
            $table->string('acquisition_source', 150)->nullable();
            $table->date('entry_date')->nullable();
            $table->string('nup_code', 100)->nullable();
            $table->string('nup_code_normalized', 100)->nullable();
            $table->string('faculty_code', 100)->nullable();
            $table->string('faculty_code_normalized', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('serial_number_normalized', 100)->nullable();
            $table->string('public_token', 64)->unique();
            // Pointer ke inventory_item_conditions.id — tidak pakai FK constraint DB
            // karena tabel condition baru dibuat setelah ini (dependensi melingkar).
            // Konsistensi dijaga di layer transaksi (lihat InventoryItem::buatDenganKondisiAwal()).
            $table->unsignedBigInteger('current_condition_id')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('archive_reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique('faculty_code_normalized');
            $table->index('nup_code_normalized');
            $table->index('serial_number_normalized');
            $table->index('current_condition_id');
            $table->index('archived_at');
            $table->index(['archived_at', 'subbuilding_id']);
            $table->index(['archived_at', 'item_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
