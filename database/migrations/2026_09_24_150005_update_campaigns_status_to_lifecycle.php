<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Phase 1.5 — Update campaigns status from approval-based to lifecycle-based
     * Old: pending/approved/rejected
     * New: active/paused/deleted
     *
     * Per REQ-ADM-02: No Approval Blocking — sponsor upload → langsung display.
     */
    public function up(): void
    {
        // Migrate existing data before changing constraints
        // 'approved' → 'active', 'pending' → 'active', 'rejected' → 'paused'
        DB::table('campaigns')->where('status', 'approved')->update(['status' => 'active']);
        DB::table('campaigns')->where('status', 'pending')->update(['status' => 'active']);
        DB::table('campaigns')->where('status', 'rejected')->update(['status' => 'paused']);

        // Drop rejection_reason column (no longer needed without approval flow)
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });

        // Add soft deletes for audit trail
        Schema::table('campaigns', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('campaigns', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
        });

        // Reverse data migration
        DB::table('campaigns')->where('status', 'active')->update(['status' => 'approved']);
        DB::table('campaigns')->where('status', 'paused')->update(['status' => 'rejected']);
    }
};
