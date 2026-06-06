<?php

namespace App\Support;

class Money
{
    public static function toCents(float|int|string|null $amount): int
    {
        return (int) round(((float) ($amount ?? 0)) * 100);
    }

    public static function fromCents(int|null $cents): float
    {
        return round(((int) ($cents ?? 0)) / 100, 2);
    }
}
