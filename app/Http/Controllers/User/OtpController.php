<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Auth\OtpSendRequest;
use App\Http\Requests\User\Auth\OtpVerifyRequest;
use App\Http\Requests\User\Auth\PasswordResetRequest;
use App\Models\Otp;
use App\Services\OtpService;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class OtpController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * @group OTP
     *
     * Send OTP
     *
     * Sends an OTP to the provided phone number for registration or password reset.
     *
     * @bodyParam phone_number string required The user's phone number. Must start with 2189 and be exactly 12 characters. Example: 218912345678
     * @bodyParam purpose string required The purpose of the OTP. Must be either "registration" or "password_reset". Example: registration
     *
     * @response 200 {
     *   "message": "OTP sent successfully."
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "phone_number": [
     *       "This phone number is already registered."
     *     ]
     *   }
     * }
     * @response 429 {
     *   "error": "You can only request an OTP once every 2 minutes."
     * }
     * @response 500 {
     *   "error": "Failed to send OTP."
     * }
     */
    public function sendOtp(OtpSendRequest $request)
    {
        $validated = $request->validated();

        // Check rate limiting
        if (Otp::where('phone_number', $validated['phone_number'])
            ->where('created_at', '>=', now()->subMinutes(2))
            ->exists()) {
            return response()->json(['error' => 'You can only request an OTP once every 2 minutes.'], 429);
        }

        if (!$this->otpService->sendOTP($validated['phone_number'], $validated['purpose'])) {
            return response()->json(['error' => 'Failed to send OTP.'], 500);
        }

        return response()->json(['message' => 'OTP sent successfully.']);
    }

    /**
     * @group OTP
     *
     * Verify OTP
     *
     * Verifies the OTP code. This does not create a user or reset password - it only marks the OTP as verified.
     * After verification, you have 30 minutes to complete registration or password reset.
     *
     * @bodyParam phone_number string required The user's phone number. Must start with 2189 and be exactly 12 characters. Example: 218912345678
     * @bodyParam otp_code string required The OTP code received by the user. Must be 6 digits. Example: 123456
     *
     * @response 200 {
     *   "message": "OTP verified successfully. You have 30 minutes to complete the process.",
     *   "purpose": "registration"
     * }
     * @response 422 {
     *   "error": "Invalid or expired OTP."
     * }
     */
    public function verifyOtp(OtpVerifyRequest $request)
    {
        $validated = $request->validated();
        
        $purpose = $this->otpService->verifyOTP(
            $validated['phone_number'], 
            $validated['otp_code']
        );

        if (!$purpose) {
            return response()->json(['error' => 'Invalid or expired OTP.'], 422);
        }

        return response()->json([
            'message' => 'OTP verified successfully. You have 30 minutes to complete the process.',
            'purpose' => $purpose
        ]);
    }

    /**
     * @group OTP
     *
     * Reset Password
     *
     * Resets the user's password after OTP verification.
     * The phone number must have a valid OTP verification for 'password_reset' purpose within the last 30 minutes.
     *
     * @bodyParam phone_number string required The user's phone number. Must start with 2189 and be exactly 12 characters. Example: 218912345678
     * @bodyParam password string required The new password. Must be at least 8 characters. Example: newpassword123
     *
     * @response 200 {
     *   "message": "Password reset successfully."
     * }
     * @response 422 {
     *   "error": "Phone number not verified or verification expired."
     * }
     * @response 404 {
     *   "error": "User not found."
     * }
     */
    public function resetPassword(PasswordResetRequest $request)
    {
        $validated = $request->validated();

        // Check if phone has valid OTP verification for password reset
        if (!Otp::hasValidVerification($validated['phone_number'], 'password_reset')) {
            return response()->json(['error' => 'Phone number not verified or verification expired.'], 422);
        }

        // Find the user
        $user = User::where('phone_number', $validated['phone_number'])
            ->where('status', 'active')
            ->first();

        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        // Update password
        $user->update([
            'password' => bcrypt($validated['password'])
        ]);

        // Revoke all tokens for this user (force re-login)
        $user->tokens()->delete();

        return response()->json(['message' => 'Password reset successfully.']);
    }
}