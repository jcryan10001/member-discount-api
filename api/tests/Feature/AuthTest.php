<?php

namespace Tests\Feature;

use App\Enums\Sector;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_pending_member_and_returns_a_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Nadia Nurse',
            'email' => 'nadia@demo.test',
            'password' => 'password',
            'sector' => 'healthcare',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.email', 'nadia@demo.test')
            ->assertJsonPath('user.verification_status', 'pending')
            ->assertJsonPath('user.is_admin', false)
            ->assertJsonStructure(['user' => ['id', 'sector'], 'token']);

        $this->assertNotEmpty($response->json('token'));
        $this->assertDatabaseHas('users', ['email' => 'nadia@demo.test', 'sector' => 'healthcare']);
    }

    public function test_registration_rejects_an_invalid_sector(): void
    {
        $this->postJson('/api/register', [
            'name' => 'X', 'email' => 'x@demo.test', 'password' => 'password', 'sector' => 'banker',
        ])->assertStatus(422)->assertJsonValidationErrors('sector');
    }

    public function test_the_returned_token_authenticates_subsequent_requests(): void
    {
        $token = $this->postJson('/api/register', [
            'name' => 'Tom', 'email' => 'tom@demo.test', 'password' => 'password', 'sector' => 'education',
        ])->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.email', 'tom@demo.test');
    }

    public function test_login_returns_a_token_for_valid_credentials(): void
    {
        User::factory()->sector(Sector::Healthcare)->create(['email' => 'nurse@demo.test']);

        $this->postJson('/api/login', ['email' => 'nurse@demo.test', 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['user' => ['id', 'email'], 'token']);
    }

    public function test_login_rejects_bad_credentials(): void
    {
        User::factory()->create(['email' => 'nurse@demo.test']);

        $this->postJson('/api/login', ['email' => 'nurse@demo.test', 'password' => 'wrong'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_user_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_user_endpoint_returns_the_current_member(): void
    {
        $user = User::factory()->verified()->sector(Sector::Carer)->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.sector', 'carer')
            ->assertJsonPath('data.verification_status', 'verified');
    }
}
