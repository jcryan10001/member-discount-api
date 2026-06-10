<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBrandRequest;
use App\Http\Requests\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Standard Laravel resource controller. Reads (index/show) are open to any
 * authenticated member; writes (store/update/destroy) are gated to admins at
 * the route layer (see routes/api.php), the same separation as a single .NET
 * controller with route-level authorization.
 */
class BrandController extends Controller
{
    /** List brands with their offers (eager-loaded to avoid N+1). */
    public function index(): AnonymousResourceCollection
    {
        return BrandResource::collection(
            Brand::with('offers')->orderBy('name')->get()
        );
    }

    public function show(Brand $brand): BrandResource
    {
        return new BrandResource($brand->load('offers'));
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $brand = Brand::create($request->validated());

        return response()->json(['data' => new BrandResource($brand)], 201);
    }

    public function update(UpdateBrandRequest $request, Brand $brand): BrandResource
    {
        $brand->update($request->validated());

        return new BrandResource($brand);
    }

    public function destroy(Brand $brand): Response
    {
        $brand->delete();

        return response()->noContent();
    }
}
