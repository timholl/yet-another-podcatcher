<?php

namespace App\Tracklist;

use RuntimeException;

interface TracklistProviderInterface
{
    /**
     * Perform a search operation.
     *
     * @return string|null A link to the most likely tracklist page or null if none was found.
     */
    public function search(string $queryString): ?string;

    /**
     * Performs an extraction of a provided tracklist URL.
     *
     * @throws RuntimeException
     * @return Chapter[]
     */
    public function get(string $tracklistUrl): array;
}
