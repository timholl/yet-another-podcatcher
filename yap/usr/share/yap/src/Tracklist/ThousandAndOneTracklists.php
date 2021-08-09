<?php

namespace App\Tracklist;

use App\Util\Logger;
use DOMDocument;
use DOMXpath;
use RuntimeException;

final class ThousandAndOneTracklists implements TracklistProviderInterface
{
    private const USER_AGENTS = [
        "Mozilla/5.0 (Linux; Android 8.0.0; SM-G960F Build/R16NW) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.84 Mobile Safari/537.36",
        "Mozilla/5.0 (Linux; Android 7.0; SM-G892A Build/NRD90M; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/60.0.3112.107 Mobile Safari/537.36",
        "Mozilla/5.0 (iPhone9,3; U; CPU iPhone OS 10_0_1 like Mac OS X) AppleWebKit/602.1.50 (KHTML, like Gecko) Version/10.0 Mobile/14A403 Safari/602.1",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.246",
        "Mozilla/5.0 (X11; CrOS x86_64 8172.45.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.64 Safari/537.36",
        "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_2) AppleWebKit/601.3.9 (KHTML, like Gecko) Version/9.0.2 Safari/601.3.9",
        "Mozilla/5.0 (Nintendo WiiU) AppleWebKit/536.30 (KHTML, like Gecko) NX/3.0.4.2.12 NintendoBrowser/4.3.1.11264.US",
        "Mozilla/5.0 (Windows Phone 10.0; Android 4.2.1; Xbox; Xbox One) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2486.0 Mobile Safari/537.36 Edge/13.10586",
    ];

    private const COOKIE_FILE = "/tmp/curl-cookie-1001.txt";

    /**
     * Shared function for using a CURL session
     */
    private function queryCurl(string $url, array $postArgs = null): ?string
    {
        $ch = curl_init();

        curl_setopt_array(
            $ch ,
            [
                CURLOPT_URL             => $url,
                CURLOPT_FOLLOWLOCATION  => false,
                CURLOPT_USERAGENT       => self::USER_AGENTS[rand(0, count(self::USER_AGENTS) - 1)], // Random, fake useragent
                CURLOPT_REFERER         => "https://www.1001tracklists.com", // Fake that we were on the main page before
                CURLOPT_TIMEOUT         => 3600,
                // ** Cookie-Options **
                CURLOPT_COOKIEJAR       => self::COOKIE_FILE,
                CURLOPT_COOKIEFILE      => self::COOKIE_FILE,
                CURLOPT_COOKIESESSION   => true,
                // ** Output **
                CURLOPT_FILE            => null,
                CURLOPT_RETURNTRANSFER  => true,
                // ** Header options / Debugging **
                // CURLOPT_HEADER          => true, // enable response header
                // CURLINFO_HEADER_OUT     => true, // enable to see the sent headers
                // CURLOPT_VERBOSE         => true,
            ]
        );

        if (null !== $postArgs) {
            curl_setopt_array(
                $ch,
                [
                    CURLOPT_POST            => true,
                    CURLOPT_POSTFIELDS      => http_build_query($postArgs),
                ]
            );
        } else {
            curl_setopt_array(
                $ch,
                [
                    CURLOPT_POST            => false,
                    CURLOPT_POSTFIELDS      => null,
                ]
            );
        }

        $result = curl_exec($ch);

        if (false === $result) {
            return null;
        }

        if (false !== strpos($result, "Your IP has been blocked")) {
            throw new RuntimeException("We got blocked.");
        }

        // Convert "false|string" into "null|string"
        return (false === $result) ? null : $result;
    }

    /**
     * @inheritDoc
     */
    public function search(string $queryString): ?string
    {
        // Prevent searching for empty strings
        if ("" === $queryString) {
            return null;
        }

        Logger::info(sprintf(
            "Performing search operation for query string '%s'",
            $queryString
        ));

        $post_args = [
            "main_search" => $queryString,
            "search_selection" => 9, // We search for tracklists
        ];

        $html = self::queryCurl("https://www.1001tracklists.com/search/result.php", $post_args);

        //
        // Parse result HTML
        //

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $ret = $doc->loadHTML($html);
        if (null === $ret || false === $ret) {
            Logger::info("Parsing the returned query HTML failed.");

            return null;
        }

        $xpath = new DOMXPath($doc);
        $linkList = $xpath->query("//div[@id='middle']/div[contains(@class, 'oItm')]/div[contains(@class, 'bCont')]/div[contains(@class, 'bTitle')]/a/@href");

        if (0 === $linkList->count()) {
            Logger::info("Did not find a single search result.");

            return null;
        }

        Logger::info(sprintf(
            "Found %d search results",
            $linkList->count())
        );

        // Element 0 exists.
        $relativeLink = $linkList->item(0)->value;
        $absoluteLink = "https://www.1001tracklists.com" . $relativeLink;

        Logger::info(sprintf(
            "Found tracklist link '%s', appended to absolute link '%s'.",
            $relativeLink,
            $absoluteLink
        ));

        return $absoluteLink;
    }

    /**
     * @inheritDoc
     */
    public function get(string $tracklistUrl): array
    {
        $html = self::queryCurl($tracklistUrl);
        if (false === $html) {
            Logger::info(sprintf(
                "Error requesting the tracklist URL '%s'.",
                $tracklistUrl
            ));

            throw new RuntimeException("Error requesting the tracklist URL.");
        }

        // Parse HTML
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $ret = $doc->loadHTML($html);
        if (null === $ret || false === $ret) {
            Logger::info(sprintf(
                "Error parsing the returned HTML from URL '%s'.",
                $tracklistUrl
            ));

            throw new RuntimeException("Error parsing the returned tracklist HTML.");
        }

        $xpath = new DOMXpath($doc);
        $tlItems = $xpath->query("//div[contains(@class, 'tlpTog')]");

        if ($tlItems->count() === 0) {
            throw new RuntimeException("Did not find any items in tracklist.");
        }

        Logger::info(sprintf(
            "Found %d items in tracklist.",
            $tlItems->count()
        ));

        foreach($tlItems as $tlItem) {

            /*
             * Get title
             */
            $ret = $xpath->query(".//span[contains(@class, 'trackValue')]", $tlItem);
            if (1 !== $ret->count()) {
                throw new RuntimeException("Could not extract tracklist item title");
            }
            $title = trim($ret->item(0)->nodeValue);
            $title = mb_convert_encoding($title, "ASCII"); // Convert title to ASCII

            /*
             * Get start time (in seconds after track begin)
             */
            $ret = $xpath->query(".//input[contains(@id, '_cue_seconds')]/@value", $tlItem);
            if (1 !== $ret->count()) {
                throw new RuntimeException("Could not extract tracklist item title");
            }
            $time = $ret->item(0)->nodeValue;

            /*
             * Store
             */
            $tracks[] = [$time, $title];
        }

        /*
         * Sort store by start time (first item)
         */
        usort($tracks, function ($a, $b) { return $a[0] > $b[0]; });

        /*
         * Generate Chapters
         */
        $ret = [];
        for($i = 0; $i < count($tracks); $i++) {

            /*
             * Start time is easy, as we know it
             */
            $timeStart = $tracks[$i][0];

            /*
             * The tricky part is the end time.
             */
            if ($i + 1 < count($tracks)) { // If there is a next track ...
                $timeEnd = $tracks[$i+1][0]; // ... simply use the next tracks start time as the current's end.
            } else { // But if there is no next track (last one) ...
                $timeEnd = $timeStart; // ... we have to use the start time as we do not know the total duration.
            }

            $title = $tracks[$i][1];

            $ret[] = new Chapter($timeStart, $timeEnd, $title);
        }

        return $ret;
    }
}
