<?php

namespace App\Util;

use App\Feed\Channel;
use App\Feed\Item;
use App\Tracklist\Chapter;

/**
 * Class for generating FfMetadata
 */
final class FfMetadata
{
    /**
     * @param Chapter[] $chapters
     */
    public static function generate(Item $item, Channel $channel, array $chapters): string
    {
        $parts = [];

        // General header
        $parts[] = ";FFMETADATA1";

        /*
         * Title: Use item (episode) title
         */
        $parts[] = sprintf(
            "title=%s",
            self::getAsOneLine($item->getTitle(), true) // Make sure to have one line only.
        );

        /*
         * Subtitle: Use the channel (podcast) title
         */
        if (null !== $channel->getSubTitle()) {
            $parts[] = sprintf(
                "subtitle=%s",
                self::getAsOneLine($channel->getTitle(), true) // Make sure to have one line only.
            );
        }

        /*
         * Add artist/author
         */
        if (null !== $channel->getAuthor()) {
            $parts[] = sprintf(
                "artist=%s",
                self::getAsOneLine($channel->getAuthor(), true) // Make sure to have one line only.
            );
        }

        /*
         * Add album name: Use combination of channel (podcast) title and item (episode) title in order to achieve
         *  per-episode 'single'-albums.
         */
        if (null !== $channel->getTitle()) {
            $parts[] = sprintf(
                "album=%s",
                self::getAsOneLine($channel->getTitle() . ' – ' . $item->getTitle(), true) // Make sure to have one line only.
            );
        }

        /*
         * Description / Comment
         */
        if (null !== $item->getDescription()) {
            $parts[] = sprintf(
                "description=%s",
                self::getAsOneLine($item->getDescription(), false) // Make sure to have one line only.
            );
        }

        /*
         * Add the channel's description as channel_description
         */
        if (null !== $channel->getDescription()) {
            $parts[] = sprintf(
                "channel_description=%s",
                self::getAsOneLine($channel->getDescription(), false)
            );
        }

        /*
         * Add date and year
         */
        if (null !== $item->getPublicationDate()) {
            $parts[] = sprintf("year=%d", $item->getPublicationDate()->format("Y"));

            // Set ISO 8601 "calendar date" (just "YYYY-MM-DD", no time or timezone)
            $parts[] = sprintf("date=%s", $item->getPublicationDate()->format("Y-m-d"));
        }

        /*
         * Add url to episode (or to channel as fallback)
         */
        if (null !== $item->getLink()) {
            $parts[] = sprintf(
                "url=%s",
                self::getAsOneLine($item->getLink(), true) // Make sure to have one line only.
            );
            $parts[] = sprintf(
                "web=%s", // Consider a synonym for "url"
                self::getAsOneLine($item->getLink(), true) // Make sure to have one line only.
            );
        } else if (null !== $channel->getLink()) { // Alternative: Use channel link instead, if exists
            $parts[] = sprintf(
                "url=%s",
                self::getAsOneLine($channel->getLink(), true) // Make sure to have one line only.
            );
            $parts[] = sprintf(
                "web=%s", // Consider a synonym for "url"
                self::getAsOneLine($channel->getLink(), true) // Make sure to have one line only.
            );
        }

        /*
         * Add copyright
         */
        if (null !== $channel->getCopyright()) {
            $parts[] = sprintf(
                "copyright=%s",
                self::getAsOneLine($channel->getCopyright(), false)
            );
        }

        /*
         * Add language
         */
        $parts[] = sprintf(
            "language=%s",
            self::getAsOneLine($channel->getLanguage(), true)
        );

        /*
         * TODO:
         *  - Publisher
         */

        /*
         * Add chapters
         */
        foreach($chapters as $chapter) {
            $parts[] = "[CHAPTER]";
            $parts[] = "TIMEBASE=1/1000"; // Time Base: Milliseconds
            $parts[] = sprintf("START=%d", $chapter->getStart() * 1000);
            $parts[] = sprintf("END=%d", $chapter->getEnd() * 1000);
            $parts[] = sprintf("TITLE=%s", $chapter->getTitle());
            $parts[] = "";
        }

        return implode("\n", $parts);
    }

    private static function getAsOneLine($text, $discard = true, $lineSeparator = "<br />"): string
    {
        // First: Remove "\r"
        $text = str_replace("\r", "", $text);

        // Second: Get lines by newline
        $lines = explode("\n", $text);

        // Based on the parameters, either return just the first line or implode by given separator
        return ($discard) ? $lines[0] : implode($lineSeparator, $lines);
    }
}
