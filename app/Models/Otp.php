<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Otp extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number', 
        'otp_code', 
        'expires_at', 
        'is_verified', 
        'verified_at',
        'purpose'
    ];

    protected $dates = ['expires_at', 'verified_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the OTP verification is still valid (within 30 minutes)
     */
    public function isVerificationValid(): bool
    {
        return $this->is_verified && 
               $this->verified_at && 
               $this->verified_at->gt(now()->subMinutes(30));
    }

    /**
     * Mark OTP as verified
     */
    public function markAsVerified(): void
    {
        $this->update([
            'is_verified' => true,
            'verified_at' => now()
        ]);
    }

    /**
     * Check if a phone number has a valid verified OTP for the given purpose
     */
    public static function hasValidVerification(string $phoneNumber, string $purpose): bool
    {
        return static::where('phone_number', $phoneNumber)
            ->where('purpose', $purpose)
            ->where('is_verified', true)
            ->where('verified_at', '>', now()->subMinutes(30))
            ->exists();
    }
}