<?php

namespace App\Http\Controllers;

use App\Enums\RequestStatus;
use App\Enums\VerificationStatus;
use App\Http\Requests\SubmitVerificationRequest;
use App\Http\Resources\VerificationRequestResource;
use App\Models\VerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class VerificationController extends Controller
{
    /** Member submits proof of eligibility; creates a pending request. */
    public function store(SubmitVerificationRequest $request): JsonResponse
    {
        $verification = $request->user()->verificationRequests()->create([
            'proof_reference' => $request->validated('proof_reference'),
            'status' => RequestStatus::Pending,
        ]);

        return response()->json([
            'data' => new VerificationRequestResource($verification),
        ], 201);
    }

    /** Admin: the pending review queue, with each submitting member. */
    public function index(): AnonymousResourceCollection
    {
        $pending = VerificationRequest::query()
            ->with('user')
            ->where('status', RequestStatus::Pending)
            ->latest()
            ->get();

        return VerificationRequestResource::collection($pending);
    }

    /** Admin approves, which flips the member to verified. */
    public function approve(Request $request, VerificationRequest $verificationRequest): JsonResponse
    {
        $this->review($request, $verificationRequest, RequestStatus::Approved, VerificationStatus::Verified);

        return response()->json([
            'data' => new VerificationRequestResource($verificationRequest->fresh('user')),
        ]);
    }

    /** Admin rejects, which marks the member rejected. */
    public function reject(Request $request, VerificationRequest $verificationRequest): JsonResponse
    {
        $this->review($request, $verificationRequest, RequestStatus::Rejected, VerificationStatus::Rejected);

        return response()->json([
            'data' => new VerificationRequestResource($verificationRequest->fresh('user')),
        ]);
    }

    /**
     * Stamp the request with its outcome and flip the member's status in one
     * transaction (the two must agree). verification_status is guarded on the
     * User model, so it's set explicitly here. This is the only place allowed
     * to grant verification.
     */
    private function review(
        Request $request,
        VerificationRequest $verificationRequest,
        RequestStatus $outcome,
        VerificationStatus $memberStatus,
    ): void {
        DB::transaction(function () use ($request, $verificationRequest, $outcome, $memberStatus): void {
            $verificationRequest->update([
                'status' => $outcome,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            $member = $verificationRequest->user;
            $member->verification_status = $memberStatus;
            $member->save();
        });
    }
}
