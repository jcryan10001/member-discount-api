<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfferRequest;
use App\Http\Requests\UpdateOfferRequest;
use App\Http\Resources\OfferResource;
use App\Models\Offer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OfferController extends Controller
{
    /**
     * Offers visible to the authenticated member: active and matching their
     * verified sector, newest first. This sector filter is the
     * "recommendation": a simple, defensible rule rather than a scoring engine
     * (the README lists scoring as out of scope).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $offers = Offer::query()
            ->with('brand')
            ->when(
                $request->user()->sector,
                fn ($query, $sector) => $query->forSector($sector),
            )
            ->latest()
            ->get();

        return OfferResource::collection($offers);
    }

    public function show(Offer $offer): OfferResource
    {
        return new OfferResource($offer->load('brand'));
    }

    public function store(StoreOfferRequest $request): JsonResponse
    {
        $offer = Offer::create($request->validated());

        return response()->json(['data' => new OfferResource($offer->load('brand'))], 201);
    }

    public function update(UpdateOfferRequest $request, Offer $offer): OfferResource
    {
        $offer->update($request->validated());

        return new OfferResource($offer->load('brand'));
    }
}
