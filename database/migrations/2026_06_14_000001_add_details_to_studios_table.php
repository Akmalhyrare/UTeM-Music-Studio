<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            $table->unsignedInteger('capacity')->nullable()->after('description');
            $table->text('equipment')->nullable()->after('capacity');
            $table->string('location', 150)->nullable()->after('equipment');
            $table->string('size', 50)->nullable()->after('location');
        });
    }

    public function down(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            $table->dropColumn(['capacity', 'equipment', 'location', 'size']);
        });
    }
};
