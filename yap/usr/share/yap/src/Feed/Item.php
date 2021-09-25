<?php

namespace App\Feed;

use DateTime;
use DateTimeInterface;
use SimpleXmlElement;
use RuntimeException;

/**
 * Class representing a feed item (= episode)
 */
final class Item
{
    /**
     * The episode title
     *
     * Required:
     * | General | iTunes |
     * | yes     | yes    |
     *
     * @var string
     */
    protected $title;

    /**
     * URL to enclosure file
     *
     * Required:
     * | General | iTunes |
     * | yes     | yes    |
     *
     * @var string
     */
    protected $enclosureUrl;

    /**
     * Size of enclosure file in bytes
     *
     * Required:
     * | General | iTunes |
     * | yes     | yes    |
     *
     * @var string
     */
    protected $enclosureLength;

    /**
     * Enclosure file's MIME type
     *
     * Required:
     * | General | iTunes |
     * | yes     | yes    |
     *
     * @var string
     */
    protected $enclosureMimeType;

    /**
     * Episode publication date
     *
     * Required:
     * | General | iTunes |
     * | no      | no     |
     *
     * @var DateTime|null
     */
    protected $publicationDate;

    /**
     * Episode description
     *
     * Required:
     * | General | iTunes |
     * | no      | no     |
     *
     * @var string|null
     */
    protected $description;

    /**
     * Link to individual episode's web presence
     *
     * Required:
     * | General | iTunes |
     * | no      | no     |
     *
     * @var string|null
     */
    protected $link;

    /**
     * URL to episode cover art.
     *
     * Required:
     * | General | iTunes |
     * | no      | no     |
     *
     * @var string|null
     */
    protected $imageUrl;

    /**
     * Decodes an Item object from parsed XML
     *
     * @throws RuntimeException
     */
    public static function fromXml(SimpleXmlElement $xml): self
    {
        $ret = new self();

        $hasItunes = in_array(Channel::XMLNS_ITUNES, $xml->getNamespaces(true));

        /*
         * Required general tags
         */

        // 'title'
        if (!isset($xml->{"title"})) {
            throw new RuntimeException("Required item attribute 'title' missing.");
        }
        $ret->title = (string) $xml->{"title"};

        // 'enclosure'
        if (!isset($xml->{"enclosure"})) {
            throw new RuntimeException("Required item attribute 'enclosure' missing.");
        }
        if (!isset($xml->{"enclosure"}->attributes()->{"url"})) {
            throw new RuntimeException("Required item enclosure attribute 'url' missing.");
        }
        $ret->enclosureUrl = (string) $xml->{"enclosure"}->attributes()->{"url"};
        if (!isset($xml->{"enclosure"}->attributes()->{"length"})) {
            throw new RuntimeException("Required item enclosure attribute 'length' missing.");
        }
        $ret->enclosureLength = (string) $xml->{"enclosure"}->attributes()->{"length"};
        if (!isset($xml->{"enclosure"}->attributes()->{"type"})) {
            throw new RuntimeException("Required item enclosure attribute 'type' missing.");
        }
        $ret->enclosureMimeType = (string) $xml->{"enclosure"}->attributes()->{"type"};

        /*
         * Optional general tags
         */

        // 'pubDate'
        if (isset($xml->{"pubDate"})) {

            // Get the provided publication date string
            $pubDate = (string) $xml->{"pubDate"};

            // Trim potential spaces (we are graceful)
            $pubDate = trim($pubDate, ' ');

            // Parse the date, expecting RFC 2822
            $ret->publicationDate = DateTime::createFromFormat(DateTimeInterface::RFC2822, $pubDate);

            // Throw an exception if the parsing failed
            if (false === $ret->publicationDate) {
                throw new RuntimeException(sprintf("Unable to parse string '%s' using RFC2822.", $pubDate));
            }
        }

        // 'description'
        if (isset($xml->{"description"})) {
            $ret->description = (string) $xml->{"description"};
        }

        // 'link'
        if (isset($xml->{"link"})) {
            $ret->link = (string) $xml->{"link"};
        }

        // 'image'
        if (isset($xml->{"image"}) && isset($xml->{"image"}->attributes()->{"href"})) {
            $ret->imageUrl = (string) $xml->{"image"}->attributes()->{"href"};
        }

        /*
         * We currently ignore the following optional tags:
         *
         *  - guid
         */

        /*
         * iTunes tags
         * If the iTunes namespace is used, it is most likely that the feed declares conformity to iTunes Podcast.
         */
        if ($hasItunes) {

            $iTunesAttributes = $xml->children(Channel::XMLNS_ITUNES, false);

            /*
             * Required iTunes tags
             */

            // (none)

            /*
             * Optional/Situational iTunes tags
             */

            // 'itunes:image'
            if (isset($iTunesAttributes->{"image"}) && isset($iTunesAttributes->{"image"}->attributes()->{"href"})) {
                // We use the iTunes image, if it has not been previously set via 'image'.
                if (!isset($ret->imageUrl)) {
                    $ret->imageUrl = (string) $iTunesAttributes->{"image"}->attributes()->{"href"};
                }
            }

            // 'itunes:title'
            if (isset($iTunesAttributes->{"title"})) {
                // We use the iTunes title, if it has not been previously set via 'title'.
                if (!isset($ret->title)) { // TODO: 'title' is required, so it can never be NOT set before!
                    $ret->title = (string) $iTunesAttributes->{"title"};
                }
            }

            /*
             * We currently ignore the following optional iTunes tags:
             *
             *  - itunes:duration (as it has too many alternative formatting options)
             *  - itunes:explicit
             *
             *  - itunes:episode
             *  - itunes:season
             *  - itunes:episodeType
             *  - itunes:block
             */
        }

        /*
         * Post-process
         */
        $ret->description = trim($ret->description);

        return $ret;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getEnclosureUrl(): string
    {
        return $this->enclosureUrl;
    }

    public function getEnclosureLength(): string
    {
        return $this->enclosureLength;
    }

    public function getEnclosureMimeType(): string
    {
        return $this->enclosureMimeType;
    }

    public function getPublicationDate(): ?DateTime
    {
        return $this->publicationDate;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }
}
