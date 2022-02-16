<?php

namespace App\Feed;

use SimpleXMLElement;
use RuntimeException;

final class Channel
{
    /*
     * iTunes podcast XML namespace
     */
    public const XMLNS_ITUNES = "http://www.itunes.com/dtds/podcast-1.0.dtd";

    /*
     * Spotify podcast XML namespace
     */
    public const XMLNS_SPOTIFY = "http://www.spotify.com/ns/rss";

    /**
     * Channel title.
     *
     * Required:
     * | General | iTunes |
     * | yes     | yes    |
     *
     * @var string
     */
    protected $title;

    /**
     * Channel subtitle.
     *
     * Required:
     * | General | iTunes |
     * | no      | no     |
     *
     * @var string|null
     */
    protected $subTitle;

    /**
     * Channel description.
     *
     * Required:
     * | General | iTunes |
     * | yes     | yes    |
     *
     * @var string
     */
    protected $description;

    /**
     * Channel image URL.
     *
     * Required:
     * | General | iTunes |
     * | no      | yes    |
     *
     * @var string|null
     */
    protected $imageUrl;

    /**
     * Channel language.
     *
     * Required:
     * | General | iTunes |
     * | yes     | yes    |
     *
     * @var string
     */
    protected $language;

    /**
     * Channel categories.
     *
     * Required:
     * | General | iTunes |
     * | no      | yes    |
     *
     * @var string[]
     */
    protected $categories = [];

    /**
     * Channel author.
     *
     * Required:
     * | General | iTunes |
     * | no      | no     |
     *
     * @var string|null
     */
    protected $author;

    /**
     * Link to the channel's/podcast's web presence.
     *
     * Required:
     * | General | iTunes |
     * | no      | no     |
     *
     * @var string|null
     */
    protected $link;

    /**
     * Channel copyright string.
     *
     * Required:
     * | General | iTunes |
     * | no      | no     |
     *
     * @var string|null
     */
    protected $copyright;

    /**
     * Channel "completed" state
     *
     * Required:
     * | General | iTunes |
     * | no      | no     |
     *
     * @var bool|null
     */
    protected $completed;

    /**
     * Potential new feed URL advertised by the feed maintainer.
     *
     * Required:
     * | General | iTunes |
     * | no      | no     |
     *
     * @var string|null
     */
    protected $newFeedUrl;

    /**
     * Episode items
     *
     * Required:
     * | General | iTunes |
     * | yes     | yes    |
     *
     * @var Item[]
     */
    protected $items;

    /**
     * Decodes a Feed object from parsed XML
     *
     * @throws RuntimeException
     */
    public static function fromXML(SimpleXMLElement $xml): self
    {
        $ret = new self();

        $hasItunes = in_array(self::XMLNS_ITUNES, $xml->getNamespaces(true));

        /*
         * Required general tags
         */

        // 'channel'
        if (!isset($xml->{"channel"})) {
            throw new RuntimeException("Required tag 'channel' missing.");
        }

        // 'title'
        if (!isset($xml->{"channel"}->{"title"})) {
            throw new RuntimeException("Required channel attribute 'title' missing.");
        }
        $ret->title = (string) $xml->{"channel"}->{"title"};

        // 'description'
        if (!isset($xml->{"channel"}->{"description"})) {
            throw new RuntimeException("Required channel attribute 'description' missing.");
        }
        $ret->description = (string) $xml->{"channel"}->{"description"};

        // 'language'
        if (!isset($xml->{"channel"}->{"language"})) {
            throw new RuntimeException("Required channel attribute 'language' missing.");
        }
        $ret->language = (string) $xml->{"channel"}->{"language"};

        /*
         * Optional general tags
         */

        // 'link'
        if (isset($xml->{"channel"}->{"link"})) {
            $ret->link = (string) $xml->{"channel"}->{"link"};
        }

        // 'copyright'
        if (isset($xml->{"channel"}->{"copyright"})) {
            $ret->copyright = (string) $xml->{"channel"}->{"copyright"};
        }

        // 'author'
        if (isset($xml->{"channel"}->{"author"})) {
            $ret->author = (string) $xml->{"channel"}->{"author"};
        }

        // 'image'
        if (isset($xml->{"channel"}->{"image"}) && isset($xml->{"channel"}->{"image"}->{"url"})) {
                $ret->imageUrl = (string) $xml->{"channel"}->{"image"}->{"url"};
        }

        // 'subtitle'
        if (isset($xml->{"channel"}->{"subtitle"})) {
            $ret->author = (string) $xml->{"channel"}->{"subtitle"};
        }

        /*
         * We currently ignore the following optional general tags:
         *
         *  - pubDate
         *  - lastBuildDate
         *  - generator
         *  - managingEditor
         *  - webMaster
         */

        /*
         * iTunes tags
         * If the iTunes namespace is used, it is most likely that the feed declares conformity to iTunes Podcast.
         */
        if ($hasItunes) {

            $iTunesAttributes = $xml->{"channel"}->children(self::XMLNS_ITUNES, false);

            /*
             * Required iTunes tags
             */

            // 'itunes:image'
            if (!isset($iTunesAttributes->{"image"})) {
                throw new RuntimeException("Required iTunes channel attribute 'image' missing.");
            }
            if (!isset($iTunesAttributes->{"image"}->attributes()->{"href"})) {
                throw new RuntimeException("Required iTunes channel attribute 'image' misses the required attribute 'href'.");
            }
            $ret->imageUrl = (string) $iTunesAttributes->{"image"}->attributes()->{"href"};

            /*
             * We currently ignore the following required iTunes tags:
             *
             *  - itunes:category (TODO!)
             *  - itunes:explicit
             */

            /*
             * Optional/Situational iTunes tags
             */

            // 'itunes:author'
            if (isset($iTunesAttributes->{"author"})) {
                // We set the iTunes author as channel author, if it has not been previously set already via 'author'.
                if (!isset($ret->author)) {
                    $ret->author = (string) $iTunesAttributes->{"author"};
                }
            }

            // 'itunes:title'
            if (isset($iTunesAttributes->{"title"})) {
                // We set the iTunes title as channel title, if it has not been previously set already via 'title'.
                if (!isset($ret->title)) {
                    $ret->title = (string) $iTunesAttributes->{"title"};
                }
            }

            // 'itunes:subtitle'
            if (isset($iTunesAttributes->{"subtitle"})) {
                // We set the iTunes subtitle as channel subtitle, if it has not been previously set already via 'subtitle'.
                if (!isset($ret->subTitle)) {
                    $ret->subTitle = (string) $iTunesAttributes->{"subtitle"};
                }
            }

            // 'itunes:complete'
            if (isset($iTunesAttributes->{"complete"})) {
                $ret->completed = "Yes" === ((string) $iTunesAttributes->{"complete"});
            }

            // 'itunes:new-feed-url'
            if (isset($iTunesAttributes->{"new-feed-url"})) {
                $ret->newFeedUrl = (string) $iTunesAttributes->{"new-feed-url"};
            }

            /*
             * We currently ignore the following optional iTunes tags:
             *
             *  - itunes:owner
             *  - itunes:type
             *  - itunes:block
             */
        }

        /*
         * Post-process
         * Beautify some attributes
         */
        $ret->description = trim($ret->description);
        $ret->subTitle = trim($ret->subTitle);
        // $ret->channelCopyright = htmlspecialchars($ret->channelCopyright);

        /*
         * Process items
         */
        foreach($xml->{"channel"}->{"item"} as $item) {
            $ret->items[] = Item::fromXml($item);
        }

        return $ret;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSubTitle(): ?string
    {
        return $this->subTitle;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @return string[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function getCopyright(): ?string
    {
        return $this->copyright;
    }

    public function getCompleted(): ?bool
    {
        return $this->completed;
    }

    public function getNewFeedUrl(): ?string
    {
        return $this->newFeedUrl;
    }

    /**
     * @return Item[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
