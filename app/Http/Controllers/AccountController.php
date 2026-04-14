<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class AccountController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'currentPassword' => ['nullable', 'string'],
            'newPassword' => ['nullable', 'string', 'min:6'],
            'confirmPassword' => ['nullable', 'string'],
        ]);

        $hasPasswordChange = filled($request->input('currentPassword'))
            || filled($request->input('newPassword'))
            || filled($request->input('confirmPassword'));

        if ($hasPasswordChange) {
            if (!filled($request->input('currentPassword'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password harus diisi!',
                ], 422);
            }

            if (!filled($request->input('newPassword')) || !filled($request->input('confirmPassword'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password baru dan konfirmasi password harus diisi!',
                ], 422);
            }

            if (!Hash::check($request->input('currentPassword'), $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password tidak valid!',
                ], 422);
            }

            if ($request->input('newPassword') !== $request->input('confirmPassword')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Konfirmasi password tidak cocok!',
                ], 422);
            }
        }

        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];

        if ($hasPasswordChange && filled($request->input('newPassword'))) {
            $user->password = Hash::make($request->input('newPassword'));
        }

        $user->save();

        if ($hasPasswordChange && filled($request->input('newPassword'))) {
            $tokenQuery = $user->tokens();

            if ($currentToken) {
                $tokenQuery->where('id', '!=', $currentToken->id);
            }

            $tokenQuery->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui!',
            'user' => $user,
        ]);
    }

    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:4096'],
        ], [
            'photo.required' => 'Foto wajib diunggah.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Format foto harus PNG, JPG, atau JPEG.',
            'photo.max' => 'Ukuran foto maksimal 4MB.',
        ]);

        $user = $request->user();

        if ($user->photo_url) {
            $this->deleteStoredPhoto($user->photo_url);
        }

        $cloudinaryUrl = $this->uploadPhotoToCloudinary($request->file('photo'));

        if ($cloudinaryUrl !== null) {
            $photoUrl = $cloudinaryUrl;
        } else {
            $path = $request->file('photo')->store('user-photos', 'public');
            $photoUrl = Storage::url($path);
        }

        $user->photo_url = $photoUrl;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diperbarui!',
            'photo_url' => $photoUrl,
            'user' => $user,
        ]);
    }

    public function deletePhoto(Request $request)
    {
        $user = $request->user();

        if ($user->photo_url) {
            $this->deleteStoredPhoto($user->photo_url);
        }

        $user->photo_url = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil dihapus!',
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        if (!filled($request->input('currentPassword'))) {
            return response()->json([
                'success' => false,
                'message' => 'Current password harus diisi!',
            ], 422);
        }

        if (!Hash::check($request->input('currentPassword'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password tidak valid!',
            ], 422);
        }

        if ($user->photo_url) {
            $this->deleteStoredPhoto($user->photo_url);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Akun berhasil dihapus!',
        ]);
    }

    private function deleteStoredPhoto(string $photoUrl): void
    {
        if ($this->deleteCloudinaryPhoto($photoUrl)) {
            return;
        }

        $path = ltrim(parse_url($photoUrl, PHP_URL_PATH) ?? '', '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        if ($path !== '') {
            Storage::disk('public')->delete($path);
        }
    }

    private function uploadPhotoToCloudinary(UploadedFile $file): ?string
    {
        $credentials = $this->parseCloudinaryUrl();

        if ($credentials === null) {
            return null;
        }

        $timestamp = time();
        $folder = 'user-photos';
        $signatureBase = "folder={$folder}&timestamp={$timestamp}{$credentials['api_secret']}";
        $signature = sha1($signatureBase);

        try {
            $response = Http::withOptions([
                'verify' => (bool) config('services.cloudinary.verify_ssl', true),
            ])->attach(
                'file',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )->post(
                "https://api.cloudinary.com/v1_1/{$credentials['cloud_name']}/image/upload",
                [
                    'api_key' => $credentials['api_key'],
                    'timestamp' => $timestamp,
                    'folder' => $folder,
                    'signature' => $signature,
                ]
            );
        } catch (Throwable $e) {
            Log::warning('Cloudinary upload failed, falling back to local storage.', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (!$response->successful()) {
            return null;
        }

        return $response->json('secure_url');
    }

    private function deleteCloudinaryPhoto(string $photoUrl): bool
    {
        $credentials = $this->parseCloudinaryUrl();

        if ($credentials === null) {
            return false;
        }

        $publicId = $this->extractCloudinaryPublicIdFromUrl($photoUrl, $credentials['cloud_name']);

        if ($publicId === null) {
            return false;
        }

        $timestamp = time();
        $signatureBase = "public_id={$publicId}&timestamp={$timestamp}{$credentials['api_secret']}";
        $signature = sha1($signatureBase);

        try {
            $response = Http::withOptions([
                'verify' => (bool) config('services.cloudinary.verify_ssl', true),
            ])->asForm()->post(
                "https://api.cloudinary.com/v1_1/{$credentials['cloud_name']}/image/destroy",
                [
                    'api_key' => $credentials['api_key'],
                    'timestamp' => $timestamp,
                    'public_id' => $publicId,
                    'signature' => $signature,
                ]
            );
        } catch (Throwable $e) {
            Log::warning('Cloudinary delete failed, falling back to local deletion.', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }

        return $response->successful();
    }

    private function parseCloudinaryUrl(): ?array
    {
        $url = (string) config('services.cloudinary.url', '');

        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);

        if (
            ($parts['scheme'] ?? null) !== 'cloudinary'
            || empty($parts['user'])
            || empty($parts['pass'])
            || empty($parts['host'])
        ) {
            return null;
        }

        return [
            'api_key' => $parts['user'],
            'api_secret' => $parts['pass'],
            'cloud_name' => $parts['host'],
        ];
    }

    private function extractCloudinaryPublicIdFromUrl(string $photoUrl, string $cloudName): ?string
    {
        $parts = parse_url($photoUrl);
        $path = $parts['path'] ?? '';

        if (!Str::contains((string) ($parts['host'] ?? ''), 'res.cloudinary.com')) {
            return null;
        }

        if (!Str::contains($path, "/{$cloudName}/image/upload/")) {
            return null;
        }

        $segments = explode('/', trim($path, '/'));
        $uploadIndex = array_search('upload', $segments, true);

        if ($uploadIndex === false || !isset($segments[$uploadIndex + 1])) {
            return null;
        }

        $publicIdSegments = array_slice($segments, $uploadIndex + 1);

        if (isset($publicIdSegments[0]) && preg_match('/^v\d+$/', $publicIdSegments[0])) {
            array_shift($publicIdSegments);
        }

        if (count($publicIdSegments) === 0) {
            return null;
        }

        $lastSegment = array_pop($publicIdSegments);
        $lastSegment = pathinfo($lastSegment, PATHINFO_FILENAME);
        $publicIdSegments[] = $lastSegment;

        return implode('/', $publicIdSegments);
    }
}