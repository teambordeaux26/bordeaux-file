<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type')->default('other');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['starts_at', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_events');
    }
};
