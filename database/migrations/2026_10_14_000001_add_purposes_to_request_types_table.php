<?php

use App\Models\RequestType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_types', function (Blueprint $table) {
            $table->json('purposes')->nullable()->after('purpose');
        });

        RequestType::query()->each(function (RequestType $type) {
            $purpose = trim((string) $type->purpose);

            $type->purposes = $purpose !== '' ? [$purpose] : [];
            $type->save();
        });
    }

    public function down(): void
    {
        Schema::table('request_types', function (Blueprint $table) {
            $table->dropColumn('purposes');
        });
    }
};
