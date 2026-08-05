<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_item_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->unsignedTinyInteger('condition_code');
            $table->text('notes');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('record_source', 20)->default('user');
            $table->timestamp('recorded_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['inventory_item_id', 'recorded_at', 'id']);
            $table->index('condition_code');
            $table->index('recorded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_item_conditions');
    }
};
