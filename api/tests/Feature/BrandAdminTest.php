<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BrandAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_list_brands_with_their_offers(): void
    {
        $brand = Brand::factory()->create();
        Offer::factory()->for($brand)->create();

        Sanctum::actingAs(User::factory()->verified()->create());

        $this->getJson('/api/brands')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $brand->id)
            ->assertJsonCount(1, 'data.0.offers');
    }

    public function test_admin_can_create_a_brand(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/admin/brands', ['name' => 'Apple', 'description' => 'Tech & gadgets'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Apple');

        $this->assertDatabaseHas('brands', ['name' => 'Apple']);
    }

    public function test_non_admin_cannot_create_a_brand(): void
    {
        Sanctum::actingAs(User::factory()->verified()->create());

        $this->postJson('/api/admin/brands', ['name' => 'Apple', 'description' => 'Tech'])
            ->assertForbidden();
    }

    public function test_guest_cannot_create_a_brand(): void
    {
        $this->postJson('/api/admin/brands', ['name' => 'Apple', 'description' => 'Tech'])
            ->assertUnauthorized();
    }

    public function test_brand_creation_is_validated(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson('/api/admin/brands', ['name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description']);
    }

    public function test_admin_can_update_a_brand(): void
    {
        $brand = Brand::factory()->create(['name' => 'Old Name']);
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->putJson("/api/admin/brands/{$brand->id}", ['name' => 'New Name'])
            ->assertOk()
            ->assertJsonPath('data.name', 'New Name');

        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'name' => 'New Name']);
    }

    public function test_admin_can_delete_a_brand(): void
    {
        $brand = Brand::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->deleteJson("/api/admin/brands/{$brand->id}")->assertNoContent();
        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }
}
