<?php

namespace App\Util;

final class Logger
{
    private static function println(string $message): void
    {
        print($message . PHP_EOL);
    }

    public static function info($message): void
    {
        self::println("[i] " . $message);
    }
}
