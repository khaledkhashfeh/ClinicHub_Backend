<?php

namespace App\Helpers;

class PhoneHelper
{
    /**
     * تطبيع رقم الهاتف إلى الصيغة الدولية (963xxxxxxxxx)
     * 
     * يقبل الأشكال التالية:
     * - 0999999999 (الشكل المحلي) → 963999999999
     * - 963999999999 (الشكل الدولي) → 963999999999
     * - +963999999999 (مع +) → 963999999999
     * 
     * @param string $phone
     * @return string
     */
    public static function normalize(string $phone): string
    {
        // إزالة المسافات والأحرف الخاصة
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // إزالة + من البداية
        $phone = ltrim($phone, '+');
        
        // إذا بدأ بـ 0، استبدله بـ 963
        if (str_starts_with($phone, '0')) {
            $phone = '963' . substr($phone, 1);
        }
        
        // إذا لم يبدأ بـ 963، أضفه
        if (!str_starts_with($phone, '963')) {
            $phone = '963' . $phone;
        }
        
        return $phone;
    }

    /**
     * التحقق من أن رقم الهاتف صالح (سوري)
     * 
     * @param string $phone
     * @return bool
     */
    public static function isValid(string $phone): bool
    {
        $normalized = self::normalize($phone);
        
        // رقم سوري صحيح: 963 + 9 أرقام (مثل: 963991234567)
        return preg_match('/^963\d{9}$/', $normalized) === 1;
    }

    /**
     * تحويل من الصيغة الدولية إلى المحلية (963999999999 → 0999999999)
     * 
     * @param string $phone
     * @return string
     */
    public static function toLocal(string $phone): string
    {
        $normalized = self::normalize($phone);
        
        if (str_starts_with($normalized, '963')) {
            return '0' . substr($normalized, 3);
        }
        
        return $phone;
    }

    /**
     * تنسيق رقم الهاتف للعرض (963999999999 → +963 99 999 9999)
     * 
     * @param string $phone
     * @return string
     */
    public static function format(string $phone): string
    {
        $normalized = self::normalize($phone);
        
        if (preg_match('/^(963)(\d{2})(\d{3})(\d{4})$/', $normalized, $matches)) {
            return '+' . $matches[1] . ' ' . $matches[2] . ' ' . $matches[3] . ' ' . $matches[4];
        }
        
        return $phone;
    }
}

