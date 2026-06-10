<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\RedemptionController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

/*
| Every route here is prefixed with /api (see bootstrap/app.php). Auth is Sanctum
| API tokens: a protected route expects an `Authorization: Bearer <token>`
| header. Admin routes add the `can:admin` gate on top of authentication.
*/

// Public.
//
// Lightweight reachability check the SPA pings on boot to wake the free-tier
// server (it sleeps after roughly 15 minutes idle) before the user interacts.
Route::get('health', fn () => ['status' => 'ok']);

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Authenticated members (Bearer token).
Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);

    // Browse brands and (sector-filtered) offers.
    Route::get('brands', [BrandController::class, 'index']);
    Route::get('brands/{brand}', [BrandController::class, 'show']);
    Route::get('offers', [OfferController::class, 'index']);
    Route::get('offers/{offer}', [OfferController::class, 'show']);

    // The redemption endpoint (the centerpiece) plus the member's own history.
    Route::post('offers/{offer}/redeem', [RedemptionController::class, 'store']);
    Route::get('redemptions', [RedemptionController::class, 'index']);

    // Member submits proof of eligibility.
    Route::post('verification', [VerificationController::class, 'store']);

    // Admin only, gated by is_admin through the 'admin' gate.
    Route::middleware('can:admin')->prefix('admin')->group(function () {
        // Brand writes. Reads (index/show) are public above; this is the standard
        // resource-controller CRUD the brief asks to demonstrate.
        Route::apiResource('brands', BrandController::class)->except(['index', 'show']);
        // Offers stay lean: create and update only, no destroy.
        Route::apiResource('offers', OfferController::class)->only(['store', 'update']);
        // Verification review queue and decisions.
        Route::get('verifications', [VerificationController::class, 'index']);
        Route::post('verifications/{verificationRequest}/approve', [VerificationController::class, 'approve']);
        Route::post('verifications/{verificationRequest}/reject', [VerificationController::class, 'reject']);
    });
});
