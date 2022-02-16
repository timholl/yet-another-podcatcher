<?php

namespace App\Util;


final class FilesystemEscaper
{
    /**
     * Escapes the provided file or directory name for safe use in filesystems.
     *
     * Replaces all characters of the input, which are not in [A-Za-z0-9\._\- ] by '-', assuming that this is safe for
     *  use in names in most filesystems.
     *
     * @param string $name A file name (without extension) or directory name.
     * @return string
     */
    public static function escapeNameForFilesystem(string $name): string
    {
        return mb_ereg_replace('/[^A-Za-z0-9\._\- ]/', '-', $name);
    }
}
