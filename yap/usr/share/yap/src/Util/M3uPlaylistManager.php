<?php

namespace App\Util;

use RuntimeException;

final class M3uPlaylistManager
{
    /**
     * @param string $baseDir The library base directory
     * @param string $playlistFileRelativePath Path to playlist file, relative to {baseDir}. Must be Filesystem-escaped!
     * @param string $itemRelativePath The item path must be relative to the basedir!
     *
     * @throws RuntimeException
     */
    public static function addItemIfNotExists(string $baseDir, string $playlistFileRelativePath, string $itemRelativePath): void
    {
        // Concatenate the playlists absolute path
        $playlistFullPath = $baseDir . DIRECTORY_SEPARATOR . $playlistFileRelativePath;

        // Check if exists, else create
        if (!file_exists($playlistFullPath)) {
            if (false === touch($playlistFullPath)) {
                throw new RuntimeException(sprintf("Unable to create playlist file '%s' on demand.", $playlistFullPath));
            }
        }

        // Assume the item is located in the basedir
        $itemFullPath = $baseDir . DIRECTORY_SEPARATOR . $itemRelativePath;

        if (!file_exists($itemFullPath)) {
            throw new RuntimeException("Invalid item provided.");
        }

        // Check if contains item
        if (self::containsLine($playlistFullPath, $itemRelativePath)) {
            return; // Silent return. Maybe add a warning here in the future
        }

        // Add item
        file_put_contents($playlistFullPath . PHP_EOL, $itemRelativePath, FILE_APPEND);
    }

    private static function containsLine(string $playlistFullPath, string $itemRelativePath): bool
    {
        $contents = file($playlistFullPath);
        if (false === $contents) {
            throw new RuntimeException(sprintf("Unable to read playlist '%s' in order to check if it contains the item to be added.", $playlistFullPath));
        }
        assert(is_array($contents));

        return in_array($itemRelativePath, $contents);
    }
}
