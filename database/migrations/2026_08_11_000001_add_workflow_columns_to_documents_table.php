<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('archived_at');
            }
            if (! Schema::hasColumn('documents', 'released_at')) {
                $table->timestamp('released_at')->nullable()->after('submitted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }
            if (Schema::hasColumn('documents', 'released_at')) {
                $table->dropColumn('released_at');
            }
        });
    }
};
