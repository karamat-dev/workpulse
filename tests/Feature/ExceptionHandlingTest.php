<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class ExceptionHandlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_api_routes_require_authentication(): void
    {
        $apiRoutes = collect(Route::getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/'))
            ->reject(fn ($route) => str_starts_with($route->uri(), 'api/__'));

        $this->assertNotEmpty($apiRoutes);

        foreach ($apiRoutes as $route) {
            $this->assertContains(
                'auth',
                $route->gatherMiddleware(),
                sprintf('API route [%s] must be protected by auth middleware.', $route->uri())
            );
        }
    }

    public function test_direct_api_access_requires_authentication_with_json_message(): void
    {
        $response = $this->get('/api/attendance/live');

        $response
            ->assertStatus(401)
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJson([
                'ok' => false,
                'message' => 'Please sign in to continue.',
            ]);

        $this->assertStringNotContainsString('<!DOCTYPE html>', $response->getContent());
    }

    public function test_direct_api_access_requires_permission_with_json_message(): void
    {
        $user = User::factory()->create([
            'role' => 'no_access',
            'employee_code' => 'NO-ACCESS',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/api/attendance/live');

        $response
            ->assertStatus(403)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJson([
                'ok' => false,
                'message' => 'You do not have permission to perform this action.',
            ]);
    }

    public function test_missing_api_routes_return_json_not_debug_page(): void
    {
        $response = $this->get('/api/not-a-real-endpoint');

        $response
            ->assertStatus(404)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJson([
                'ok' => false,
                'message' => 'We could not find what you were looking for.',
            ]);

        $this->assertStringNotContainsString('<!DOCTYPE html>', $response->getContent());
    }

    public function test_api_server_errors_return_end_user_json(): void
    {
        Route::get('/api/__exception-test', static function () {
            throw new RuntimeException('SQLSTATE[42S02]: Base table or view not found');
        });

        $response = $this->getJson('/api/__exception-test');

        $response
            ->assertStatus(500)
            ->assertJson([
                'ok' => false,
                'message' => 'Something went wrong while processing your request. Please try again.',
            ]);

        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
    }

    public function test_api_validation_errors_keep_user_friendly_message(): void
    {
        Route::post('/api/__validation-test', static function () {
            throw ValidationException::withMessages([
                'email' => 'Please enter a valid official email.',
            ]);
        });

        $response = $this->postJson('/api/__validation-test');

        $response
            ->assertStatus(422)
            ->assertJson([
                'ok' => false,
                'message' => 'Please enter a valid official email.',
            ])
            ->assertJsonValidationErrors('email');
    }
}
