<?php

namespace App\Support;

class AddonsSignature
{
    public static function from(array $addons): string
    {
        $normalized = array_values(array_map('strval', $addons));
        sort($normalized);

        return hash('sha256', json_encode($normalized));
    }

    public static function normalize(array $addons): array
    {
        $normalized = array_values(array_map('strval', $addons));
        sort($normalized);

        return $normalized;
    }
}
