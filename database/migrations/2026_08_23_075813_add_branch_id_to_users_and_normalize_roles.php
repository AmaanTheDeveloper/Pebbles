<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')
                ->nullable()
                ->after('role')
                ->constrained()
                ->nullOnDelete();
        });

        DB::table('users')
            ->where('role', 'superadmin')
            ->update(['role' => 'super_admin']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'manager')
            ->update([
                'role' => 'user',
                'branch_id' => null,
            ]);

        DB::table('users')
            ->where('role', 'super_admin')
            ->update(['role' => 'superadmin']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
