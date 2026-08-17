<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrowings', function (Blueprint $table) {
            $table->renameColumn('borrow_date', 'pickup_date');
            $table->renameColumn('due_date', 'return_date');
        });

        Schema::table('borrowings', function (Blueprint $table) {
            $table->timestamp('collected_at')->nullable()->after('return_date');
            $table->foreignId('collected_by')->nullable()->after('collected_at')
                ->constrained('staff', 'staff_id')->onDelete('set null');

            $table->timestamp('returned_at')->nullable()->after('collected_by');
            $table->foreignId('returned_by')->nullable()->after('returned_at')
                ->constrained('staff', 'staff_id')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('borrowings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('collected_by');
            $table->dropConstrainedForeignId('returned_by');
            $table->dropColumn(['collected_at', 'returned_at']);
        });

        Schema::table('borrowings', function (Blueprint $table) {
            $table->renameColumn('pickup_date', 'borrow_date');
            $table->renameColumn('return_date', 'due_date');
        });
    }
};
