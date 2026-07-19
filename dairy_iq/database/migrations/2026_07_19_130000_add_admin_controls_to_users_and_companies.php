<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role');
            $table->boolean('force_password_change')->default(false)->after('is_active');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'force_password_change']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
