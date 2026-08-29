<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('signing_name')->nullable()->after('position');
            $table->string('signing_title')->nullable()->after('signing_name');
            $table->string('signature_path')->nullable()->after('signing_title');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->string('signer_name')->nullable()->after('issued_by');
            $table->string('signer_title')->nullable()->after('signer_name');
            $table->string('signer_signature_path')->nullable()->after('signer_title');
        });

        Schema::table('system_settings', function (Blueprint $table) {
            $table->foreignId('official_signer_user_id')
                ->nullable()
                ->after('employee_pages')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('official_signer_user_id');
        });

        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn(['signer_name', 'signer_title', 'signer_signature_path']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['signing_name', 'signing_title', 'signature_path']);
        });
    }
};
