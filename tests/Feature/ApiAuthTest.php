<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_bearer_token_and_user(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'token_type',
                'user' => ['id', 'name', 'email'],
            ])
            ->assertJsonPath('token_type', 'Bearer');
    }

    public function test_protected_route_requires_token(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_user_endpoint_with_valid_token(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user');

        $response->assertOk()->assertJsonPath('user.email', $user->email);
    }

    public function test_api_docs_page_is_public(): void
    {
        $this->get(route('api.docs'))->assertOk();
    }

    public function test_questionnaires_index_returns_paginated_json(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/questionnaires');

        $response->assertOk()
            ->assertJsonStructure(['data', 'links'])
            ->assertJsonPath('current_page', 1);
    }
}
