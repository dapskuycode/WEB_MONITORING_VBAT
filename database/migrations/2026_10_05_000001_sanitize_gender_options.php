<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * ADMIN-02: Sanitize gender options to strictly male/female only.
     */
    public function up(): void
    {
        // Sanitize any invalid or 'other' gender records to null
        DB::table('users')
            ->whereNotIn('gender', ['male', 'female', 'Laki-laki', 'Perempuan'])
            ->whereNotNull('gender')
            ->update(['gender' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed for sanitization
    }
};
