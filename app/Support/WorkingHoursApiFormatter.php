<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * تحويل working_hours المخزّنة (مفاتيح أيام + start/end أو off) إلى مصفوفة للعرض بالإنجليزية مع AM/PM.
 * الترتيب: السبت → الجمعة كما في مواصفات التطبيق.
 */
final class WorkingHoursApiFormatter
{
    /** @var array<string, string> */
    private const DAY_KEYS = [
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
    ];

    /**
     * @return list<array{day: string, is_closed: bool, from: ?string, to: ?string}>
     */
    public static function format(?array $hours): array
    {
        if (!is_array($hours) || $hours === []) {
            return self::allClosed();
        }

        $out = [];
        foreach (self::DAY_KEYS as $key => $label) {
            $day = $hours[$key] ?? null;
            if (!is_array($day)) {
                $out[] = self::closedRow($label);
                continue;
            }
            if (!empty($day['off'])) {
                $out[] = self::closedRow($label);
                continue;
            }
            $start = $day['start'] ?? null;
            $end = $day['end'] ?? null;
            if ($start === null || $start === '' || $end === null || $end === '') {
                $out[] = self::closedRow($label);
                continue;
            }

            try {
                $from = self::toAmPm((string) $start);
                $to = self::toAmPm((string) $end);
            } catch (\Throwable) {
                $out[] = self::closedRow($label);
                continue;
            }

            $out[] = [
                'day' => $label,
                'is_closed' => false,
                'from' => $from,
                'to' => $to,
            ];
        }

        return $out;
    }

    /**
     * @return list<array{day: string, is_closed: bool, from: null, to: null}>
     */
    private static function allClosed(): array
    {
        $out = [];
        foreach (self::DAY_KEYS as $label) {
            $out[] = self::closedRow($label);
        }

        return $out;
    }

    /**
     * @return array{day: string, is_closed: bool, from: null, to: null}
     */
    private static function closedRow(string $dayLabel): array
    {
        return [
            'day' => $dayLabel,
            'is_closed' => true,
            'from' => null,
            'to' => null,
        ];
    }

    private static function toAmPm(string $time): string
    {
        $time = trim($time);
        $parsed = Carbon::createFromFormat('H:i', $time);
        if ($parsed === false) {
            $parsed = Carbon::createFromFormat('G:i', $time);
        }
        if ($parsed === false) {
            throw new \InvalidArgumentException('Invalid time');
        }

        return $parsed->format('h:i A');
    }
}
