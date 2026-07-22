<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('hotline')->default('(052) 555-0198');
            $table->string('office_hours')->default('Monday to Friday, 8:00 AM – 5:00 PM');
            $table->json('public_search_fields')->nullable();
            $table->json('public_search_categories')->nullable();
            $table->json('employee_pages')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
