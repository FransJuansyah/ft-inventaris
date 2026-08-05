<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_record_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_record_id')->constrained('maintenance_records')->cascadeOnDelete();
            $table->string('storage_disk', 50)->default('public');
            $table->string('path', 500);
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique('path');
            $table->unique(['maintenance_record_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_record_images');
    }
};
