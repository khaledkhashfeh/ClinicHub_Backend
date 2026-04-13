<?php

namespace App\Services;

use App\Models\Clinic;
use App\Models\MedicalCenter;
use Carbon\Carbon;

class ClinicOpenStatusService
{
    /**
     * Determine if clinic is accepting patients "right now" based on working_hours JSON.
     * Expects keys: sunday, monday, ... with ['start' => 'H:i', 'end' => 'H:i'] or ['off' => true].
     */
    public function isOpenNow(Clinic $clinic): bool
    {
        $status = $this->resolve($clinic);

        return $status['is_open'];
    }

    /**
     * @return array{is_open: bool, status_text: string}
     */
    public function resolve(Clinic $clinic): array
    {
        return $this->resolveFromWorkingHours($clinic->working_hours);
    }

    /**
     * نفس منطق العيادة لساعات عمل المركز الطبي.
     *
     * @return array{is_open: bool, status_text: string}
     */
    public function resolveMedicalCenter(MedicalCenter $center): array
    {
        return $this->resolveFromWorkingHours($center->working_hours);
    }

    /**
     * @return array{is_open: bool, status_text: string}
     */
    public function resolveFromWorkingHours(mixed $hours): array
    {
        if (!is_array($hours) || $hours === []) {
            return [
                'is_open' => false,
                'status_text' => 'لم يتم تحديد ساعات العمل',
            ];
        }

        $now = Carbon::now(config('app.timezone'));
        $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $dayKey = $days[$now->dayOfWeek];

        if (!isset($hours[$dayKey])) {
            return [
                'is_open' => false,
                'status_text' => 'مغلق اليوم',
            ];
        }

        $day = $hours[$dayKey];

        if (is_array($day) && !empty($day['off'])) {
            return [
                'is_open' => false,
                'status_text' => 'مغلق اليوم',
            ];
        }

        if (!is_array($day) || empty($day['start']) || empty($day['end'])) {
            return [
                'is_open' => false,
                'status_text' => 'ساعات العمل غير مكتملة',
            ];
        }

        try {
            $start = Carbon::parse($now->toDateString() . ' ' . $day['start'], config('app.timezone'));
            $end = Carbon::parse($now->toDateString() . ' ' . $day['end'], config('app.timezone'));
        } catch (\Throwable) {
            return [
                'is_open' => false,
                'status_text' => 'تنسيق ساعات العمل غير صالح',
            ];
        }

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $open = ($now->gte($start) && $now->lte($end));

        $closeLabel = $end->translatedFormat('h:i a');

        if ($open) {
            return [
                'is_open' => true,
                'status_text' => 'مفتوح - يغلق الساعة ' . $closeLabel,
            ];
        }

        if ($now->lessThan($start)) {
            $openLabel = $start->translatedFormat('h:i a');

            return [
                'is_open' => false,
                'status_text' => 'مغلق الآن - يفتح الساعة ' . $openLabel,
            ];
        }

        return [
            'is_open' => false,
            'status_text' => 'مغلق الآن',
        ];
    }
}

