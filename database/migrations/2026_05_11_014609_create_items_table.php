<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id('item_id');
            $table->foreignId('category_id')->constrained('categories', 'category_id')->onDelete('restrict');
            $table->string('item_name', 100);
            $table->text('item_description')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('available_quantity')->default(0);
            $table->string('condition_status', 50)->default('good');
            $table->string('item_status', 50)->default('available');
            $table->date('date_added')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};