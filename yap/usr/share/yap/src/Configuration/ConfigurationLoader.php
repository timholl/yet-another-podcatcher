<?php

namespace App\Configuration;

use RuntimeException;

final class ConfigurationLoader
{
    /*
     * Configuration filepath.
     */
    private const CONFIG_FILEPATH = "/etc/yap/config.json";

    public static function loadConfiguration(): ?Configuration
    {
        /*
         * Read config file
         */
        $json = file_get_contents(self::CONFIG_FILEPATH);
        if (false === $json) {
            throw new RuntimeException("Unable to read configuration from file.");
        }

        return Configuration::fromJson($json);
    }
}
