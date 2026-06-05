<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_update_without_username_keeps_existing_username(): void
    {
        $user = User::create([
            'first_name' => 'Old',
            'last_name' => 'Name',
            'username' => 'oldusername',
            'email' => 'old@example.com',
            'password' => Hash::make('password123'),
            'photo_url' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/user/update', [
            'first_name' => 'New',
            'last_name' => null,
            'email' => 'new@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.first_name', 'New')
            ->assertJsonPath('user.last_name', null)
            ->assertJsonPath('user.username', 'oldusername')
            ->assertJsonPath('user.email', 'new@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'New',
            'last_name' => null,
            'username' => 'oldusername',
            'email' => 'new@example.com',
        ]);
    }
}
