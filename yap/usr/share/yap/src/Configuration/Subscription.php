<?php

namespace App\Configuration;

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

    /**
     * Only download the last recent {$recent} episodes, or null.
     *
     * @var int|null
     */
    private $recent;

    public function __construct(string $title, string $feedUrl, bool $externalTracklistMergeEnabled, ?int $recent = null)
    {
        $this->title = $title;
        $this->feedUrl = $feedUrl;
        $this->externalTracklistMergeEnabled = $externalTracklistMergeEnabled;
        $this->recent = $recent;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getFeedUrl(): string
    {
        return $this->feedUrl;
    }

    public function isExternalTracklistMergeEnabled(): bool
    {
        return $this->externalTracklistMergeEnabled;
    }

    public function getRecent(): ?int
    {
        return $this->recent;
    }
}
