<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\Auth\OtpSendRequest;
use App\Http\Requests\User\Auth\OtpVerifyRequest;
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
     *       "The phone number must be 12 digits."
     *     ]
     *   }
     * }
     * @response 500 {
     *   "error": "Failed to send OTP."
     * }
     */
    public function sendOtp(OtpSendRequest $request)
    {
        $validated = $request->validated();

        if (Otp::where('phone_number', $validated['phone_number'])
            ->where('created_at', '>=', now()->subMinutes(2))
            ->exists()) {
            return response()->json(['error' => 'You can only request an OTP once every 2 minutes.'], 429);
        }
        
        if (!$this->otpService->generateOTP(
            $validated['phone_number'], 
            $validated['purpose']
        )) {
            return response()->json(['error' => 'Failed to send OTP.'], 500);
        }

        return response()->json(['message' => 'OTP sent successfully.']);
    }

    /**
     * @group OTP
     *
     * Verify OTP
     *
     * Verifies the OTP and completes the registration or password reset process.
     *
     * @bodyParam phone_number string required The user's phone number. Must start with 2189 and be exactly 12 characters. Example: 218912345678
     * @bodyParam otp_code string required The OTP code received by the user. Must be 6 digits. Example: 123456
     * @bodyParam password string required The new password. Must be at least 8 characters. Example: newpassword123
     *
     * @response 200 {
     *   "message": "registration completed successfully."
     * }
     * @response 422 {
     *   "error": "Invalid or expired OTP."
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "password": [
     *       "The password must be at least 8 characters."
     *     ]
     *   }
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

        $userData = [
            'phone_number' => $validated['phone_number'],
            'password' => bcrypt($validated['password']),
            'type' => 'user',
            'status' => 'inactive'
        ];

        if ($purpose === 'registration') {
            User::create($userData);
        } else {
            $user = User::where('phone_number', $validated['phone_number'])->firstOrFail();
            $user->update(['password' => $userData['password']]);
        }

        return response()->json(['message' => $purpose . ' completed successfully.']);
    }
}