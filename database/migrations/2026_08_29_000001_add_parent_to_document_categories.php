<?php

use App\Models\DocumentCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_categories', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('id')
                ->constrained('document_categories')
                ->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0)->after('description');
        });

        DocumentCategory::syncCatalog();
    }

    public function down(): void
    {
        Schema::table('document_categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn('sort_order');
        });
    }
};
