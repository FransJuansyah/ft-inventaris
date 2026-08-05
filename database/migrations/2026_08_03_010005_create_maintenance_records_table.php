<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            $table->text('description');
            $table->unsignedBigInteger('cost_amount');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('record_source', 20)->default('user');
            $table->timestamp('recorded_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['inventory_item_id', 'recorded_at']);
            $table->index('recorded_by');
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
    }
};
