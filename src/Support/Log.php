<?php

namespace Elcreator\aSocialAuth\Support;

class Log
{
    public static function info(string $message): void
    {
        static::write(1, $message);
    }

    public static function warning(string $message): void
    {
        static::write(2, $message);
    }

    public static function error(string $message): void
    {
        static::write(3, $message);
    }

    protected static function write(int $type, string $message): void
    {
        if (!function_exists('evo')) {
            return;
        }

        try {
            evo()->logEvent(0, $type, htmlspecialchars($message, ENT_QUOTES, 'UTF-8'), 'aSocialAuth');
        } catch (\Throwable $e) {
            // Silently fail – logging should never break the request.
        }
    }
}
