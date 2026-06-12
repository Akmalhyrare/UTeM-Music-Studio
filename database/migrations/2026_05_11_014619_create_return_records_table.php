<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_records', function (Blueprint $table) {
            $table->id('return_id');
            $table->foreignId('borrow_id')->constrained('borrowings', 'borrow_id')->onDelete('restrict');
            $table->foreignId('item_id')->constrained('items', 'item_id')->onDelete('restrict');
            $table->foreignId('staff_id')->nullable()->constrained('staff', 'staff_id')->onDelete('set null');
            $table->date('return_date');
            $table->integer('quantity_returned')->default(1);
            $table->string('item_condition', 50)->default('good');
            $table->text('damage_note')->nullable();
            $table->string('return_status', 50)->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_records');
    }
};