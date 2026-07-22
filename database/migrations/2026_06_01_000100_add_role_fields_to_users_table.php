<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('employee')->after('password');
            $table->string('department')->nullable()->after('role');
            $table->string('position')->nullable()->after('department');
            $table->string('phone', 30)->nullable()->after('position');
            $table->string('status')->default('active')->after('phone');
            $table->timestamp('last_login_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'department',
                'position',
                'phone',
                'status',
                'last_login_at',
            ]);
        });
    }
};
