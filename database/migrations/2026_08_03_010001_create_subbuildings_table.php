<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subbuildings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained('buildings')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('normalized_name', 150);
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['building_id', 'normalized_name']);
            $table->index('archived_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subbuildings');
    }
};
