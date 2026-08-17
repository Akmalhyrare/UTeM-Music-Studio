<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Convert borrowing_details into a pure bridge table: drop the
     * surrogate borrow_detail_id PK and make (borrow_id, item_id) the
     * primary key. Safe to run because borrowing_details currently has
     * zero duplicate (borrow_id, item_id) pairs.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE borrowing_details DROP CONSTRAINT borrowing_details_pkey');

        Schema::table('borrowing_details', function (Blueprint $table) {
            $table->dropColumn('borrow_detail_id');
        });

        DB::statement('ALTER TABLE borrowing_details ADD PRIMARY KEY (borrow_id, item_id)');
    }

    /**
     * Restores the surrogate-key structure. Note: new borrow_detail_id
     * values are reassigned in row order and will NOT match the original
     * ids — restore from a pre-migration backup if exact id fidelity matters.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE borrowing_details DROP CONSTRAINT borrowing_details_pkey');

        Schema::table('borrowing_details', function (Blueprint $table) {
            $table->id('borrow_detail_id');
        });

        DB::statement('ALTER TABLE borrowing_details DROP CONSTRAINT IF EXISTS borrowing_details_pkey');
        DB::statement('ALTER TABLE borrowing_details ADD PRIMARY KEY (borrow_detail_id)');
    }
};
