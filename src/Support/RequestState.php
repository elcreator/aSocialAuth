<?php

namespace Elcreator\aSocialAuth\Support;

/**
 * State that lasts one request, in a worker too.
 *
 * A static outlives the request there: "already emitted" would hold for every
 * later visitor. Each request has its own REQUEST_TIME_FLOAT, set by PHP or by
 * the worker, so a different one means the values belong to someone else.
 */
class RequestState
{
    protected static ?string $request = null;

    /** @var array<string, mixed> */
    protected static array $values = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        static::sync();

        return static::$values[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::sync();
        static::$values[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset(static::$values[$key]);
    }

    protected static function sync(): void
    {
        $request = (string) ($_SERVER['REQUEST_TIME_FLOAT'] ?? '');

        if ($request !== static::$request) {
            static::$request = $request;
            static::$values  = [];
        }
    }
}
