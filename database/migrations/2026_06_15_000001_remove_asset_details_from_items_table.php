<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['asset_tag', 'manufacturer', 'model_number', 'serial_number', 'purchase_year']);
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('asset_tag', 30)->nullable()->unique()->after('item_id');
            $table->string('manufacturer', 100)->nullable()->after('item_name');
            $table->string('model_number', 100)->nullable()->after('manufacturer');
            $table->string('serial_number', 100)->nullable()->unique()->after('model_number');
            $table->unsignedSmallInteger('purchase_year')->nullable()->after('serial_number');
        });
    }
};
