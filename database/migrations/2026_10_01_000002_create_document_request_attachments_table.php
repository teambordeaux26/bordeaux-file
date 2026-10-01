<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_request_id')->constrained('document_requests')->cascadeOnDelete();
            $table->string('path');
            $table->string('name');
            $table->timestamps();
        });

        if (Schema::hasColumn('document_requests', 'attachment_path')) {
            $existing = DB::table('document_requests')
                ->whereNotNull('attachment_path')
                ->get(['id', 'attachment_path', 'attachment_name']);

            foreach ($existing as $row) {
                DB::table('document_request_attachments')->insert([
                    'document_request_id' => $row->id,
                    'path' => $row->attachment_path,
                    'name' => $row->attachment_name ?: 'attachment',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Schema::table('document_requests', function (Blueprint $table) {
                $table->dropColumn(['attachment_path', 'attachment_name']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_request_attachments');

        if (! Schema::hasColumn('document_requests', 'attachment_path')) {
            Schema::table('document_requests', function (Blueprint $table) {
                $table->string('attachment_path')->nullable()->after('details');
                $table->string('attachment_name')->nullable()->after('attachment_path');
            });
        }
    }
};
