<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('inspections')) {
            return;
        }

        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            
            // UUID for client-side offline sync reference
            $table->uuid('uuid')->unique();
            
            // Foreign key to inspector
            $table->foreignId('inspector_id')->constrained('users')->onDelete('cascade');
            
            // JSON storage for all form data
            // Matches the formSchema structure from frontend
            $table->json('form_data');
            
            // Sync status tracking
            $table->enum('status', ['draft', 'submitted', 'reviewed', 'approved'])->default('draft');
            
            // Track last successful sync
            $table->timestamp('synced_at')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for efficient queries
            $table->index('inspector_id');
            $table->index('status');
            $table->index('created_at');
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspections');
    }
};
