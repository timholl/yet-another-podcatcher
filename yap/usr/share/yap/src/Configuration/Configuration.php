<?php

namespace App\Configuration;

use RuntimeException;

/**
 * Configuration model class
 */
final class Configuration
{
    /**
     * @var Subscription[]
     */
    private $subscriptions;

    /**
     * Base directory for output
     * @var string
     */
    private $libraryDirectory;

    /**
     * @param Subscription[] $subscriptions
     */
    public function __construct(array $subscriptions, $libraryDirectory)
    {
        $this->subscriptions = $subscriptions;
        $this->libraryDirectory = $libraryDirectory;
    }

    /**
     * @return Subscription[]
     */
    public function getSubscriptions(): array
    {
        return $this->subscriptions;
    }

    public function getLibraryDirectory(): string
    {
        return $this->libraryDirectory;
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

        /*
         * Library directory
         */
        if (!isset($jsonArray['libraryDirectory'])) {
            throw new RuntimeException("Required attribute 'libraryDirectory' missing.");
        }
        $libraryDirectory = (string) $jsonArray['libraryDirectory'];

        /*
         * Subscriptions
         */
        if (!isset($jsonArray['subscriptions'])) {
            throw new RuntimeException("Required attribute 'subscriptions' missing.");
        }

        if (!is_array($jsonArray['subscriptions'])) {
            throw new RuntimeException("Required attribute 'subscriptions' is not an array.");
        }

        $subscriptions = [];
        foreach($jsonArray['subscriptions'] as $subscription) {

            /*
             * Title
             */
            if (!isset($subscription['title'])) {
                throw new RuntimeException("Required attribute 'title' missing for subscription.");
            }
            $title = (string) $subscription['title'];

            /*
             * Feed URL
             */
            if (!isset($subscription['feedUrl'])) {
                throw new RuntimeException("Required attribute 'feedUrl' missing for subscription.");
            }
            $feedUrl = (string) $subscription['feedUrl'];

            /*
             * External tracklist merge enabled (yes/no)
             */
            if (!isset($subscription['externalTracklistMergeEnabled'])) {
                throw new RuntimeException("Required attribute 'externalTracklistMergeEnabled' missing for subscription.");
            }
            $externalTracklist = (boolean) $subscription['externalTracklistMergeEnabled'];

            /*
             * Optional attribute 'recent' (defaults to null)
             */
            $recent = null;
            if (isset($subscription['recent'])) {
                $recent = (int) $subscription['recent'];
            }

            $subscriptions[] = new Subscription($title, $feedUrl, $externalTracklist, $recent);
        }

        /*
         * Instantiate
         */
        return new self(
            $subscriptions,
            $libraryDirectory
        );
    }
}
