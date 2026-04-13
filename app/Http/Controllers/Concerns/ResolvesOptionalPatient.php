<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Patient;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

trait ResolvesOptionalPatient
{
    /**
     * مريض مسجّل إن وُجد توكن صالح لـ User مرتبط بـ patient؛ وإلا null.
     */
    protected function optionalPatient(): ?Patient
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (JWTException) {
            return null;
        }

        if (!$user || !$user->patient) {
            return null;
        }

        return $user->patient;
    }
}
