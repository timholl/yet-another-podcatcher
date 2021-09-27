<?php

namespace App;

use App\Configuration\ConfigurationLoader;
use App\Feed\Channel;
use App\Storage\AlreadyDownloadedHelper;
use App\Tracklist\Chapter;
use App\Tracklist\ThousandAndOneTracklists;
use App\Tracklist\TracklistProviderInterface;
use App\Util\FfMetadata;
use App\Util\Logger;
use App\Util\TempDir;
use RuntimeException;

require __DIR__ . '/vendor/autoload.php';

Logger::info("Startup");

/*
 * Load the configuration
 */
$config = ConfigurationLoader::loadConfiguration();

if (null === $config) {
    throw new RuntimeException("Error while loading configuration. Exiting.");
}

Logger::info("Loaded configuration");

// Tell libxml to use internal errors
libxml_use_internal_errors(true);

/*
 * For each feed:
 *
 *  * Query the Feed contents
 *  * For each found item:
 *    * Check if either GUID or Download URL are present
 *
 * Download-Store-Helper
 */
foreach($config->getSubscriptions() as $subscription) {

    Logger::info(sprintf(
        "Processing feed '%s' (%s) ...",
        $subscription->getTitle(),
        $subscription->getFeedUrl()
    ));

    // Perform query
    $contents = file_get_contents($subscription->getFeedUrl());
    if (false === $contents) {
        Logger::info("Error getting the feed! Skipping.");
        continue;
    }

    // Parse as XML
    $xml = simplexml_load_string($contents);
    if (false === $xml) {
        Logger::info("Error parsing the feeds content! Skipping.");
        continue;
    }

    // Decode to object
    $feed = Channel::fromXML($xml);

    Logger::info(sprintf(
        "Loaded feed '%s'. Currently %d entries found.",
        $feed->getTitle(),
        count($feed->getItems())
    ));

    /*
     * Print warning if the feed maintainer advertises a new feed URL, and this is not the currently used one.
     */
    if (null !== $feed->getNewFeedUrl() && $subscription->getFeedUrl() !== $feed->getNewFeedUrl()) {
        Logger::info(sprintf(
            'IMPORTANT: Unhandled feed URL change detected. "%s" -> "%s".',
            $subscription->getFeedUrl(),
            $feed->getNewFeedUrl()
        ));
    }

    /*
     * Print warning if podcast is marked as "completed" (no further episode will be added)
     */
    if (true === $feed->getCompleted()) {
        Logger::info(sprintf(
            'IMPORTANT: Feed is marked as "completed", so no further episodes will be published.'
        ));
    }

    /*
     * Counts how many items of the current feed have already been processed.
     */
    $processedFeedItemsCounter = 0;

    // Process items
    foreach($feed->getItems() as $item) {

        // Increment processed items counter
        $processedFeedItemsCounter++;

        Logger::info(sprintf(
            "Processing item '%s'",
            $item->getTitle()
        ));

        // Skip episode if already downloaded
        if (AlreadyDownloadedHelper::alreadyDownloaded($config, $item->getEnclosureUrl())) {
            Logger::info(sprintf(
                "Already downloaded episode '%s'. Skipping.",
                $item->getTitle()
            ));

            // Continue with the next item of feed.
            continue;
        }

        /*
         * If we reach here, we know that the current item has not been downloaded yet.
         *
         * However, if the "recent" feature on the subscription is enabled, we only download the specified amount of items.
         * If we have already processed ($processedFeedItemsCounter) more than this amount of the current feed, we mark
         *  the remaining items as already downloaded and skip further processing.
         */
        if (null !== $subscription->getRecent() && $processedFeedItemsCounter > $subscription->getRecent()) {

            // Store the current episode as downloaded
            AlreadyDownloadedHelper::storeAsDownloaded($config, $item->getEnclosureUrl());

            Logger::info(sprintf(
                "Marked URL %s as downloaded (skipped due to not recent).",
                $item->getEnclosureUrl()
            ));

            // Continue with the next item of feed.
            continue;
        }

        Logger::info(sprintf(
            "Starting download of new episode '%s' ...",
            $item->getTitle()
        ));

        // Generate download identifier
        $hash = substr(md5(time() . rand()), 0, 8);

        // Prepare temporary working directory
        $workingDir = TempDir::createTempDir($hash);
        if (null === $workingDir) {
            throw new RuntimeException("Unable to create temporary working directory!");
        }

        // Switch into
        chdir($workingDir);

        Logger::info(sprintf("Using temporary working directory %s.", $workingDir));

        /*
         * Cover art
         */
        $imageUrl = null;
        if (null !== $item->getImageUrl()) {
            // Use the episode-specific image url
            $imageUrl = $item->getImageUrl();
        } else if (null !== $feed->getImageUrl()) {
            // Use the channel general image url
            $imageUrl = $feed->getImageUrl();
        }
        if ($imageUrl) {
            Logger::info(sprintf(
                "Downloading cover art file '%s' ...",
                $imageUrl
            ));

            $ret = file_put_contents("./cover", fopen($imageUrl, 'r'));
            if (false === $ret) {
                throw new RuntimeException("Cover art download failed.");
            }

            Logger::info(sprintf(
                "Successfully downloaded %d bytes of cover art.",
                $ret
            ));

            // Convert to PNG
            exec("convert ./cover ./cover.png");

            // Delete original
            exec("rm ./cover");

            if (file_exists("./cover.png")) {
                Logger::info("Converted cover art file to PNG.");
            } else {
                Logger::info("Warning: Conversion of cover art file to PNG apparently failed. Proceeding without.");
            }
        }

        /*
         * Enclosure file download
         */
        Logger::info(sprintf(
            "Downloading enclosure file '%s' ...",
            $item->getEnclosureUrl()
        ));

        $ret = file_put_contents("./audio", fopen($item->getEnclosureUrl(), 'r'));
        if (false === $ret) {
            throw new RuntimeException("Enclosure file download failed.");
        }

        Logger::info(sprintf(
            "Successfully downloaded %d bytes.",
            $ret
        ));

        assert(file_exists("./audio")); // Must be true if download did not fail.

        // Post-Processing

        /*
         * Convert the provided "./audio" file to Matroska audio (MKA)
         *
         * - Keep the original audio codec
         * - Drop any potential video streams
         * - Do not drop any metadata, nor chapters
         * - Force matroska container output format
         */
        exec("ffmpeg -i ./audio -c:a copy -vn -f matroska ./audio2");

        // Check result
        if (!file_exists("./audio2")) {
            throw new RuntimeException("Conversion step failed.");
        }

        Logger::info("Conversion step successful.");

        // Clean up
        exec("rm ./audio && mv ./audio2 ./audio");

        /**
         * Check if chapters present and external merge is enabled.
         *
         * @var Chapter[] $chapters
         */
        $chapters = [];
        if ($subscription->isExternalTracklistMergeEnabled()) {
            Logger::info("External tracklist merge is enabled for the current subscription.");

            // The string to search for.
            $queryString = $feed->getTitle() . " " . $item->getTitle();

            Logger::info(sprintf(
                "Going to retrieve external tracklist using query string '%s'.",
                $queryString
            ));

            /**
             * Decide the TracklistProviderInterface implementation.
             * We use 1001 Tracklists here as default.
             *
             * @var TracklistProviderInterface
             */
            $tracklistProvider = new ThousandAndOneTracklists();

            try {

                // Let the tracklist provider search
                $tracklistUrl = $tracklistProvider->search($queryString);

                if (null !== $tracklistUrl) {
                    Logger::info(sprintf(
                        "Found tracklist with URL '%s'.",
                        $tracklistUrl
                    ));

                    // Let the tracklist provider extract the chapters
                    $chapters = $tracklistProvider->get($tracklistUrl);
                } else {
                    Logger::info(sprintf(
                        "Could not find a tracklist for the provided query string '%s'. Going to fail hard.",
                        $queryString
                    ));

                    throw new RuntimeException(
                        "Error searching or extracting the tracklist. Maybe we got blacklisted?"
                    );
                }

            } catch(RuntimeException $exception) {

                /*
                 * If we reach here, one of the following happened:
                 *  - Searching for the query string filed (connection error, IP blocked, no search result, ...)
                 *  - Extracting the tracklist items failed (no items, no time, ...)
                 */

                Logger::info(sprintf(
                    "Something went wrong during tracklist extraction: %s.",
                    $exception->getMessage()
                ));

                /*
                 * Fail hard, only if tracklist merge is critical.
                 */
                if ($subscription->isExternalTracklistMergeCritical()) {
                    throw new RuntimeException("Tracklist merge is configured critical and it failed.");
                }

                Logger::info("Tracklist merge is NOT configured critical, proceeding without tracklist.");
            }
        }

        /*
         * Add Metadata
         */

        // Write FfMetadata file
        file_put_contents("./metadata.txt", FfMetadata::generate($item, $feed, $chapters));

        // Add to file
        exec("ffmpeg -i ./audio -i metadata.txt -codec copy -vn -map_metadata 1 -f matroska ./audio2");

        // Check result
        if (!file_exists("./audio2")) {
            throw new RuntimeException("Metadata merge failed.");
        }

        Logger::info("Metadata merge successful");

        // Clean up
        exec("rm ./audio && mv ./audio2 ./audio");

        /*
         * Attach potential cover art
         */
        if (file_exists("./cover.png")) {
            Logger::info("Cover art file present, starting merge.");

            exec("ffmpeg -i ./audio -codec copy -vn -map_metadata 0 -f matroska -attach cover.png -metadata:s:t mimetype=image/png ./audio2");

            // Check result
            if (!file_exists("./audio2")) {
                throw new RuntimeException("Merge of cover art failed.");
            }

            Logger::info("Cover art file merge successful.");

            exec("rm ./audio && mv ./audio2 ./audio");
        }

        /*
         * Move to output directory
         */

        // Destination directory setup
        $destinationDirectory = $config->getLibraryDirectory() . "/" . $feed->getTitle();
        if (!is_dir($destinationDirectory)) {
            Logger::info(sprintf(
                "Output directory '%s' does not exist yet, going to create.",
                $destinationDirectory
            ));

            if (false === mkdir($destinationDirectory)) {
                throw new RuntimeException("Could not create output directory!");
            }
        }
        assert(is_dir($destinationDirectory));

        $destination = $destinationDirectory . "/" . $feed->getTitle() . " - " . $item->getTitle() . ".mka";

        exec(sprintf("mv ./audio %s", escapeshellarg($destination)));

        Logger::info(sprintf(
            "Moved file to destination '%s' ...",
            $destination
        ));

        // Clean up temporary directory
        TempDir::removeTempDir($workingDir);

        // Store as downloaded
        AlreadyDownloadedHelper::storeAsDownloaded($config, $item->getEnclosureUrl());

        Logger::info(sprintf(
            "Marked URL %s as downloaded.",
            $item->getEnclosureUrl()
        ));

        Logger::info(sprintf(
            "Finished processing of episode %s.",
            $item->getTitle()
        ));

        // sleep(3);
    }

    Logger::info(sprintf(
        "Finished processing items from subscription '%s'.",
        $feed->getTitle()
    ));
}

Logger::info("Finished processing all subscriptions. Exiting.");
