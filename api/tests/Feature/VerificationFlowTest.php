<?php

namespace Tests\Feature;

use App\Enums\RequestStatus;
use App\Enums\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VerificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_submit_a_verification_request(): void
    {
        $user = User::factory()->sector(Sector::Healthcare)->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/verification', ['proof_reference' => 'NHS staff ID: ABC-12345'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.proof_reference', 'NHS staff ID: ABC-12345');

        $this->assertDatabaseHas('verification_requests', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_list_pending_verification_requests(): void
    {
        $member = User::factory()->sector(Sector::Healthcare)->create();
        $member->verificationRequests()->create([
            'proof_reference' => 'proof', 'status' => RequestStatus::Pending,
        ]);

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson('/api/admin/verifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.user.id', $member->id);
    }

    public function test_admin_approval_verifies_the_member(): void
    {
        $member = User::factory()->sector(Sector::Healthcare)->create();
        $request = $member->verificationRequests()->create([
            'proof_reference' => 'proof', 'status' => RequestStatus::Pending,
        ]);
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->postJson("/api/admin/verifications/{$request->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertSame('verified', $member->fresh()->verification_status->value);
        $this->assertDatabaseHas('verification_requests', [
            'id' => $request->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_admin_rejection_marks_the_member_rejected(): void
    {
        $member = User::factory()->sector(Sector::Healthcare)->create();
        $request = $member->verificationRequests()->create([
            'proof_reference' => 'proof', 'status' => RequestStatus::Pending,
        ]);

        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson("/api/admin/verifications/{$request->id}/reject")
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->assertSame('rejected', $member->fresh()->verification_status->value);
    }

    public function test_non_admin_cannot_access_the_admin_queue(): void
    {
        Sanctum::actingAs(User::factory()->verified()->create());

        $this->getJson('/api/admin/verifications')->assertForbidden();
    }
}
