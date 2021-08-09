<?php

namespace App\Util;

final class TempDir
{
    public static function createTempDir(string $dirname): ?string
    {

        // Get system base directory
        $basedir = sys_get_temp_dir();

        // Trim potential trailing slashes
        $basedir = rtrim($basedir, DIRECTORY_SEPARATOR);

        // Check privileges
        if (!is_dir($basedir) || !is_writable($basedir)) {
            return null;
        }

        $path = $basedir . DIRECTORY_SEPARATOR . $dirname;

        // Check if already exists
        if (is_dir($path)) {
            return null;
        }

        // Create directory as it does not exist yet
        if (false === mkdir($path)) {
            return null;
        }

        return $path;
    }

    public static function removeTempDir(string $dirname): void
    {

        // Validate is dir
        if (!is_dir($dirname)) {
            return;
        }

        // Validate is dir inside system temp directory
        if (0 !== strpos($dirname, sys_get_temp_dir())) {
            return; // Ignore
        }

        // Remove (need for shell call since PHP is not able to remove recursively yet)
        exec(sprintf('rm -rf "%s"', $dirname));
    }
}
