<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('studio_images', function (Blueprint $table) {
            $table->id('image_id');
            $table->foreignId('studio_id')->constrained('studios', 'studio_id')->onDelete('cascade');
            $table->string('image_path');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['studio_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('studio_images');
    }
};
