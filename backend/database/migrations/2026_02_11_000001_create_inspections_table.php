<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('inspector_id')->constrained('users');
            $table->string('landing_site_name')->nullable();
            $table->string('owner_operator')->nullable();
            $table->date('inspection_date')->nullable();
            $table->json('form_data');
            $table->string('sync_status')->default('synced');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
