<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve account IDs, passwords, and all existing repair assignments.
        DB::table('users')->where('role', 'staff')->update([
            'role' => 'mechanic',
            'session_version' => DB::raw('session_version + 1'),
        ]);
    }

    public function down(): void
    {
        DB::table('users')->whereIn('role', ['secretary', 'mechanic'])->update([
            'role' => 'staff',
            'session_version' => DB::raw('session_version + 1'),
        ]);
    }
};
