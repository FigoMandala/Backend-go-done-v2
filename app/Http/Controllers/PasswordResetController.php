<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Mail\ResetPasswordMail;

class PasswordResetController extends Controller
{
    // Step 1: Send OTP
    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        // Generate 6-digit OTP
        $otp = sprintf("%06d", mt_rand(1, 999999));

        // Delete old tokens
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Insert new token
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($otp),
            'created_at' => Carbon::now()
        ]);

        // Fetch User First Name to say Hi
        $user = User::where('email', $request->email)->first();

        // Send Email
        try {
            Mail::to($request->email)->send(new ResetPasswordMail($otp, $user->first_name));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Mail Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => 'Failed to send email. Check your SMTP settings.'], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully to ' . $request->email
        ]);
    }

    // Step 2: Validate OTP & Verify
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6'
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->otp, $record->token)) {
            return response()->json(['success' => false, 'error' => 'Invalid or expired OTP.'], 400);
        }

        // Optional: Check expiration (e.g., 15 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['success' => false, 'error' => 'OTP has expired.'], 400);
        }

        return response()->json(['success' => true, 'message' => 'OTP verified successfully.']);
    }

    // Step 3: Reset Password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
            'password' => 'required|min:6'
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->otp, $record->token)) {
            return response()->json(['success' => false, 'error' => 'Invalid or expired OTP.'], 400);
        }

        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['success' => false, 'error' => 'OTP has expired.'], 400);
        }

        // Update password
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete token so it can't be reused
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. You can now login.'
        ]);
    }
}
