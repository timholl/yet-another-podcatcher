<?php

namespace App\Storage;

use App\Configuration\Configuration;

final class AlreadyDownloadedHelper
{
    /**
     * Returns true iff the provided URL already has been downloaded into the library pointed by the configuration
     */
    public static function alreadyDownloaded(Configuration $configuration, string $url): bool
    {
        $storeFilePath = self::getStoreFilePath($configuration);
        self::init($storeFilePath);

        // Read file as array of strings
        $contents = file($storeFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($contents === false) {
            return false;
        }

        // Search. If is in array, the URL has been downloaded already.
        return in_array($url, $contents);
    }

    public static function storeAsDownloaded(Configuration $configuration, string $url): void
    {
        /*
         * We do not want to store a URL twice
         */
        if (self::alreadyDownloaded($configuration, $url)) {
            return;
        }

        $storeFilePath = self::getStoreFilePath($configuration);
        self::init($storeFilePath);

        // Write URL to file, assuming the file ends with a newline
        file_put_contents($storeFilePath, $url . PHP_EOL, FILE_APPEND);
    }

    private static function getStoreFilePath(Configuration $configuration): string
    {
        return $configuration->getLibraryDirectory() . "/store.txt";
    }

    private static function init(string $storeFilePath): void
    {
        // Create file on demand
        if (!file_exists($storeFilePath)) {
            touch($storeFilePath);
        }
    }
}
