<?php

namespace App\Http\Controllers;

use App\Http\Resources\RedemptionResource;
use App\Models\Offer;
use App\Services\RedemptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RedemptionController extends Controller
{
    // Constructor injection from the service container, the same DI pattern as
    // a .NET controller.
    public function __construct(private readonly RedemptionService $redemptions) {}

    /**
     * Redeem an offer for the current member. Every business rule lives in
     * RedemptionService; on failure it throws a typed RedemptionException that
     * the global handler renders as JSON with the right status, so this method
     * stays a thin pass-through (no try/catch, no rules here).
     */
    public function store(Request $request, Offer $offer): JsonResponse
    {
        $redemption = $this->redemptions->redeem($request->user(), $offer);

        return response()->json([
            'data' => new RedemptionResource($redemption->load('offer.brand')),
        ], 201);
    }

    /** The member's own redemption history, newest first. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $redemptions = $request->user()
            ->redemptions()
            ->with('offer.brand')
            ->latest('redeemed_at')
            ->get();

        return RedemptionResource::collection($redemptions);
    }
}
