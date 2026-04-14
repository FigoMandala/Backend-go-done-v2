<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const DEFAULT_CATEGORIES = [
        'Sekolah',
        'Kerja',
        'Pribadi',
        'Kesehatan',
        'Keuangan',
    ];

    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
            ]);

            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'photo_url' => null,
            ]);

            $defaultCategories = array_map(function ($name) use ($user) {
                return [
                    'user_id' => $user->id,
                    'category_name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, self::DEFAULT_CATEGORIES);

            Category::insert($defaultCategories);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Registrasi berhasil!',
                'token' => $token,
                'user' => $user
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid!',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat registrasi!'
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'emailOrUsername' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginField = filter_var($request->emailOrUsername, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($loginField, $request->emailOrUsername)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kombinasi email/username dan password salah!'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil!',
            'token' => $token,
            'user' => $user
        ], 200);
    }
}
