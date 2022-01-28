<?php

namespace Weble\RevisoApi;

class Utils
{
    public static function studlyString(string $value): string
    {
        $value = ucwords(str_replace([
            '-',
            '_'
        ], ' ', $value));

        return str_replace(' ', '', $value);
    }

    public static function snakeString(string $value, string $delimiter = '-'): string
    {
        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));

            $value = strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
        }

        return $value;
    }
}
