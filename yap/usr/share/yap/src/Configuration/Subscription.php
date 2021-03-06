<?php

namespace App\Configuration;

final class Subscription
{
    /**
     * Title of the podcast.
     * Required.
     *
     * @var string
     */
    private $title;

    /**
     * Feed URL.
     * Required.
     *
     * @var string
     */
    private $feedUrl;

    /**
     * Merge an external tracklist (1001 tracklists).
     * Required.
     *
     * @var bool
     */
    private $externalTracklistMergeEnabled;

    /**
     * Optional: Is tracklist extraction critical?
     *
     * This value is only relevant if {$externalTracklistMergeEnabled} is true and something goes wrong during
     *  extraction of the tracklist.
     *
     * Defaults to true.
     *
     * @var bool
     */
    private $externalTracklistMergeCritical;

    /**
     * Optional: Is this feed enabled?
     *
     * Defaults to true.
     *
     * @var bool
     */
    private $enabled;

    /**
     * Optional: Only download the last recent {$recent} episodes, or null.
     *
     * Defaults to null.
     *
     * @var int|null
     */
    private $recent;

    /**
     * Optional: Add newly downloaded items to M3U playlist.
     *
     * Defaults to false.
     *
     * @var bool
     */
    private $createPlaylist;

    public function __construct(
        string $title,
        string $feedUrl,
        bool $externalTracklistMergeEnabled,
        bool $externalTracklistMergeCritical,
        bool $enabled,
        bool $createPlaylist,
        ?int $recent = null
    ) {
        $this->title = $title;
        $this->feedUrl = $feedUrl;
        $this->externalTracklistMergeEnabled = $externalTracklistMergeEnabled;
        $this->externalTracklistMergeCritical = $externalTracklistMergeCritical;
        $this->enabled = $enabled;
        $this->createPlaylist = $createPlaylist;
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

    public function isExternalTracklistMergeCritical(): bool
    {
        return $this->externalTracklistMergeCritical;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getRecent(): ?int
    {
        return $this->recent;
    }

    public function isCreatePlaylist(): bool
    {
        return $this->createPlaylist;
    }
}
