<?php

namespace App\Support;

/**
 * تطبيع بسيط للبحث العربي (أ/إ/آ → ا، ة → ه، إلخ) لتحسين تطابق الاستعلامات.
 */
class ArabicSearchNormalizer
{
    public static function normalize(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $map = [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا',
            'ى' => 'ي',
            'ة' => 'ه',
            'ؤ' => 'و',
            'ئ' => 'ي',
        ];

        return strtr($text, $map);
    }

    /**
     * تعبير SQL لتطبيع عمود نصي (MySQL / PostgreSQL متوافق مع REPLACE المتداخل).
     */
    public static function sqlNormalizeColumn(string $column): string
    {
        $c = $column;

        return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE({$c},''),'أ','ا'),'إ','ا'),'آ','ا'),'ة','ه'),'ى','ي'),'ئ','ي')";
    }
}
