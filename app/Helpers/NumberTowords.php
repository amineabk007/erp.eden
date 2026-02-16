<?php

namespace App\Helpers;

use NumberFormatter;

class NumberToWords
{
    public static function dirhams($amount)
    {
        $formatter = new NumberFormatter('fr_FR', NumberFormatter::SPELLOUT);

        $int = floor($amount);
        $dec = round(($amount - $int) * 100);

        $words = ucfirst($formatter->format($int)) . ' dirhams';

        if ($dec > 0) {
            $words .= ' et ' . $formatter->format($dec) . ' centimes';
        }

        return $words;
    }
}
