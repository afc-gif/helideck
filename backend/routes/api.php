<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\InspectionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * ============================================================================
 * AUTHENTICATION ENDPOINTS (Public)
 * ============================================================================
 */

// Login endpoint - returns token for offline sync
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Register endpoint - create new inspector account
Route::post('/register', [AuthController::class, 'register'])->name('register');

/**
 * ============================================================================
 * PROTECTED ENDPOINTS (Require auth:sanctum token)
 * ============================================================================
 */

Route::middleware('auth:sanctum')->group(function () {

    /**
     * Authentication
     */
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/user', [AuthController::class, 'user'])->name('user');

    /**
     * Inspections - Sync & Read
     */
    // Sync offline queue - accepts array of inspections
    Route::post('/inspections/sync', [InspectionController::class, 'sync'])->name('inspections.sync');

    // List inspections with pagination and filtering
    Route::get('/inspections', [InspectionController::class, 'index'])->name('inspections.index');

    // Get single inspection by UUID
    Route::get('/inspections/{uuid}', [InspectionController::class, 'show'])->name('inspections.show');

    /**
     * Export
     */
    // Export single inspection as PDF
    Route::get('/inspections/{uuid}/export/pdf', [InspectionController::class, 'exportPdf'])->name('inspections.export.pdf');

    // Export all inspections as CSV
    Route::get('/inspections/export/csv', [InspectionController::class, 'exportCsv'])->name('inspections.export.csv');
});
