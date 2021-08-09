<?php

namespace App\Configuration;

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
}
