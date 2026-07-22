<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->string('response_file_path')->nullable()->after('status');
            $table->string('response_file_name')->nullable()->after('response_file_path');
            $table->timestamp('email_sent_at')->nullable()->after('processed_at');
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropColumn(['response_file_path', 'response_file_name', 'email_sent_at']);
        });
    }
};
