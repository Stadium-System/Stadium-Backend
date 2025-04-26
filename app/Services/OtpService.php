<?php

namespace App\Services;

use App\Models\Otp;
use Illuminate\Support\Facades\Http;

class OtpService
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = 'https://isend.com.ly';
        $this->apiKey = config('services.isend_sms.api_key');
    }

    public function generateOTP($phone, $purpose)
    {
        $otpCode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        // Delete any existing unverified OTPs for the phone number for storage optimization
        Otp::where('phone_number', $phone)
            ->where('is_verified', false)
            ->delete();

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/api/http/sms/send', [
                    'api_token' => $this->apiKey,
                    'recipient' => $phone,
                    'sender_id' => 'iSend',
                    'message' => "Your Stadium verification code is: $otpCode. The code will expire in 5 minutes.",
                    'type' => 'plain',
                ]);

        if ($response->successful()) {
            Otp::create([
                'phone_number' => $phone,
                'otp_code' => $otpCode,
                'expires_at' => now()->addMinutes(5),
                'purpose' => $purpose,
                'is_verified' => false
            ]);
        }

        return $response->successful();
    }

    public function verifyOTP($phone, $otpCode)
    {
        $otp = Otp::where('phone_number', $phone)
            ->where('otp_code', $otpCode)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp || $otp->is_verified) {
            return false;
        }

        $otp->update(['is_verified' => true]);
        return $otp->purpose;
    }
}