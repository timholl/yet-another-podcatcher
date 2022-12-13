<?php

namespace App\Util;

final class Logger
{
    public const VERBOSITY_QUIET = -1;

    /**
     * Normal verbosity.
     * Only prints un-usual events that should have the users attention.
     * Examples:
     *   - new episode downloaded
     *   - errors
     */
    public const VERBOSITY_NORMAL = 0;

    /**
     * All output, including generic messeges like "startup"/"exiting" etc.
     */
    public const VERBOSITY_VERBOSE = 1;

    /**
     * @var int The current maximum verbosity level that will be logged by the logger.
     */
    private static $maxVerbosity = self::VERBOSITY_NORMAL;

    public static function setMaxVerbosity(int $maxVerbosity): void
    {
        self::$maxVerbosity = $maxVerbosity;
    }

    private static function println(string $message, int $verbosity): void
    {
        // Omit messages with a verbosity above the current max verbosity.
        if ($verbosity > self::$maxVerbosity) {
            return;
        }

        print($message . PHP_EOL);
    }

    public static function info($message, ?int $verbosity = self::VERBOSITY_NORMAL): void
    {
        self::println("[i] " . $message, $verbosity);
    }
}
