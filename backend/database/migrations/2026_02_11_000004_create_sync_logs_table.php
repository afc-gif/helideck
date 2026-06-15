<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tracks all sync attempts for debugging and audit purposes
     */
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            
            // Reference to inspection
            $table->foreignId('inspection_id')->constrained('inspections')->onDelete('cascade');
            
            // What happened during sync
            $table->enum('action', ['created', 'updated', 'skipped', 'failed'])->default('created');
            
            // Reason if skipped or failed
            $table->text('message')->nullable();
            
            // Sync payload for debugging
            $table->json('payload')->nullable();
            
            // Inspector who triggered sync
            $table->foreignId('inspector_id')->constrained('users')->onDelete('cascade');
            
            // Timestamp
            $table->timestamp('synced_at')->useCurrent();
            
            // Indexes
            $table->index('inspection_id');
            $table->index('action');
            $table->index('synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_logs');
    }
};
