<?php

namespace App\Util;

final class CommandExecutor
{
    /**
     * @param string $command
     * @return bool True iff exit code is zero, aka execution was successful.
     */
    public static function execute(string $command): bool
    {
        $output = [];
        $result_code = null;

        exec($command, $output, $result_code);

        return 0 === $result_code;
    }
}
