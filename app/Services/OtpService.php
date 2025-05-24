<?php

namespace App\Services;

use App\Models\Otp;
use ISend\SMS\Facades\ISend;
use ISend\SMS\Exceptions\ISendException;
use Illuminate\Support\Facades\Log;

class OtpService
{
    /**
     * Generate and send OTP
     */
    public function sendOTP(string $phone, string $purpose): bool
    {
        try {
            // Delete any existing unverified OTPs for this phone and purpose
            Otp::where('phone_number', $phone)
                ->where('purpose', $purpose)
                ->where('is_verified', false)
                ->delete();

            // Generate 6-digit OTP
            $otpCode = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Create OTP record
            Otp::create([
                'phone_number' => $phone,
                'otp_code' => $otpCode,
                'purpose' => $purpose,
                'expires_at' => now()->addMinutes(5),
                'is_verified' => false
            ]);

            // Send SMS using Suliman's iSend Laravel package
            $message = "Your Stadium verification code is: $otpCode. The code will expire in 5 minutes.";
            
            $response = ISend::to($phone)
                ->message($message)
                ->send();

            // Check if the SMS was sent successfully
            $smsId = $response->getId();
            
            if ($smsId) {
                Log::info("OTP sent successfully to {$phone}", [
                    'purpose' => $purpose,
                    'sms_id' => $smsId
                ]);
                return true;
            } else {
                Log::error("Failed to send OTP to {$phone}", [
                    'purpose' => $purpose,
                    'response' => $response->getLastResponse()
                ]);
                return false;
            }

        } catch (ISendException $e) {
            Log::error("iSend API error while sending OTP to {$phone}", [
                'purpose' => $purpose,
                'error' => $e->getMessage(),
                'status_code' => $e->getStatusCode(),
                'response_data' => $e->getResponseData(),
                'request_data' => $e->getRequestData()
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("Exception while sending OTP to {$phone}", [
                'purpose' => $purpose,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Verify OTP code
     */
    public function verifyOTP(string $phone, string $otpCode): ?string
    {
        try {
            $otp = Otp::where('phone_number', $phone)
                ->where('otp_code', $otpCode)
                ->where('expires_at', '>', now())
                ->where('is_verified', false)
                ->first();

            if (!$otp) {
                Log::warning("Invalid or expired OTP attempt for {$phone}");
                return null;
            }

            // Mark as verified
            $otp->markAsVerified();

            Log::info("OTP verified successfully for {$phone}", ['purpose' => $otp->purpose]);
            
            return $otp->purpose;

        } catch (\Exception $e) {
            Log::error("Exception while verifying OTP for {$phone}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

}