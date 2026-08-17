<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_images', function (Blueprint $table) {
            $table->id('image_id');
            $table->foreignId('item_id')->constrained('items', 'item_id')->onDelete('cascade');
            $table->string('image_path');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['item_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_images');
    }
};
