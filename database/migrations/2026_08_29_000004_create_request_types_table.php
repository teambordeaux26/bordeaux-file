<?php

use App\Models\DocumentRequest;
use App\Models\RequestType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('purpose');
            $table->boolean('issues_certificate')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('document_requests', function (Blueprint $table) {
            $table->foreignId('request_type_id')
                ->nullable()
                ->after('request_type')
                ->constrained('request_types')
                ->nullOnDelete();
            $table->string('purpose')->nullable()->after('request_type_id');
        });

        RequestType::syncDefaults();

        DocumentRequest::query()->each(function (DocumentRequest $request) {
            $type = RequestType::query()
                ->where('name', $request->request_type)
                ->first();

            if (! $type) {
                return;
            }

            $request->forceFill([
                'request_type_id' => $type->id,
                'purpose'         => $type->purpose,
            ])->save();
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('request_type_id');
            $table->dropColumn('purpose');
        });

        Schema::dropIfExists('request_types');
    }
};
