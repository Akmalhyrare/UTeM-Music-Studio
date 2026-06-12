<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id('maintenance_id');
            $table->foreignId('item_id')->constrained('items', 'item_id')->onDelete('restrict');
            $table->foreignId('staff_id')->nullable()->constrained('staff', 'staff_id')->onDelete('set null');
            $table->foreignId('return_id')->nullable()->constrained('return_records', 'return_id')->onDelete('set null');
            $table->date('report_date')->useCurrent();
            $table->string('issue_type', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('maintenance_status', 50)->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};