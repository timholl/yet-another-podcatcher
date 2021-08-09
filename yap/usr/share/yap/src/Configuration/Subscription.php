<?php

namespace App\Configuration;

use RuntimeException;

final class Subscription
{
    /**
     * Title of the podcast
     *
     * @var string
     */
    private $title;

    /**
     * Feed URL
     *
     * @var string
     */
    private $feedUrl;

    /**
     * Merge an external tracklist (1001 tracklists)
     *
     * @var bool
     */
    private $externalTracklistMergeEnabled;

    public function __construct(string $title, string $feedUrl, bool $externalTracklistMergeEnabled)
    {
        $this->title = $title;
        $this->feedUrl = $feedUrl;
        $this->externalTracklistMergeEnabled = $externalTracklistMergeEnabled;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getFeedUrl(): string
    {
        return $this->feedUrl;
    }

    public function externalTracklistMergeEnabled(): bool
    {
        return $this->externalTracklistMergeEnabled;
    }

    /**
     * Decodes a provided JSON string
     *
     * @throws RuntimeException
     */
    public static function fromJson(string $jsonString): self
    {
        // Decode
        $jsonArray = json_decode($jsonString, true);
        if (null === $jsonArray) {
            throw new RuntimeException("Invalid JSON!");
        }

        if (!isset($jsonArray['title'])) {
            throw new RuntimeException("Title missing.");
        }

        if (!isset($jsonArray['feedUrl'])) {
            throw new RuntimeException("Feed URL missing.");
        }

        if (!isset($jsonArray['externalTracklistMergeEnabled'])) {
            throw new RuntimeException("Missing attribute externalTracklistMergeEnabled");
        }

        return new self(
            (string) $jsonArray['title'],
            (string) $jsonArray['feedUrl'],
            (boolean) $jsonArray['externalTracklistMergeEnabled']
        );
    }
}
