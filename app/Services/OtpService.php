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
     * Format phone number for Evolution API (add country code if needed)
     *
     * @param string $phone Phone number
     * @return string Formatted phone number
     */
    private function formatPhoneNumber($phone)
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If phone starts with 0, remove it
        if (strpos($phone, '0') === 0) {
            $phone = substr($phone, 1);
        }
        
        // If phone doesn't start with country code (963 for Syria), add it
        if (strlen($phone) === 9 && !str_starts_with($phone, '963')) {
            $phone = '963' . $phone;
        }
        
        return $phone;
    }

    /**
     * Send OTP to phone number via Evolution API
     *
     * @param string $phone Phone number to send OTP to
     * @param string|null $otp Optional OTP code. If not provided, a new one will be generated
     * @return array
     */
    public function sendOtp($phone, $otp = null)
    {
        if ($otp === null) {
            $otp = $this->generateOtp();
        }
        
        $cacheKey = "otp_{$phone}";
        
        // Store OTP in cache for 5 minutes (300 seconds)
        Cache::put($cacheKey, $otp, now()->addMinutes(5));
        
        // Format phone number for Evolution API
        $formattedPhone = $this->formatPhoneNumber($phone);
        
        // Send OTP via Evolution API
        $message = "رمز التحقق الخاص بك هو: {$otp}. هذا الرمز سينتهي خلال 5 دقائق.";
        
        return $this->evolutionApiService->sendMessage($formattedPhone, $message);
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
