<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OtpService
{
    private $evolutionApiService;

    public function __construct(EvolutionApiService $evolutionApiService)
    {
        $this->evolutionApiService = $evolutionApiService;
    }

    /**
     * Generate a 6-digit numeric OTP
     *
     * @return string
     */
    public function generateOtp()
    {
        return str_pad(random_int(100000, 999999), 6, "0", STR_PAD_LEFT);
    }

    /**
     * Send OTP to phone number via Evolution API
     *
     * @param string $phone Phone number to send OTP to
     * @return array
     */
    public function sendOtp($phone)
    {
        $otp = $this->generateOtp();
        $cacheKey = "otp_{$phone}";
        
        // Store OTP in cache for 5 minutes (300 seconds)
        Cache::put($cacheKey, $otp, now()->addMinutes(5));
        
        // Send OTP via Evolution API
        $message = "Your verification code is: {$otp}. This code will expire in 5 minutes.";
        
        return $this->evolutionApiService->sendMessage($phone, $message);
    }

    /**
     * Verify OTP for a phone number
     *
     * @param string $phone Phone number
     * @param string $otp OTP code to verify
     * @return bool
     */
    public function verifyOtp($phone, $otp)
    {
        $cacheKey = "otp_{$phone}";
        $storedOtp = Cache::get($cacheKey);
        
        if ($storedOtp && $storedOtp === $otp) {
            // Delete the OTP after successful verification
            Cache::forget($cacheKey);
            return true;
        }
        
        return false;
    }

    /**
     * Resend OTP for a phone number
     *
     * @param string $phone Phone number
     * @return array
     */
    public function resendOtp($phone)
    {
        return $this->sendOtp($phone);
    }
}
