<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_account_requires_current_password(): void
    {
        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'photo_url' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/user/delete', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Current password harus diisi!',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_delete_account_rejects_wrong_current_password(): void
    {
        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'photo_url' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/user/delete', [
            'currentPassword' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Current password tidak valid!',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_delete_account_succeeds_with_valid_current_password(): void
    {
        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'photo_url' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/user/delete', [
            'currentPassword' => 'password123',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Akun berhasil dihapus!',
            ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }
}
