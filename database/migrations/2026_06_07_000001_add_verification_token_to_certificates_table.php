<?php

use App\Models\Certificate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->string('verification_token', 64)->nullable()->unique()->after('certificate_no');
        });

        Certificate::query()
            ->whereNull('verification_token')
            ->each(function (Certificate $certificate) {
                $certificate->update([
                    'verification_token' => Str::uuid()->toString(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('verification_token');
        });
    }
};
