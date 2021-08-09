<?php

namespace App\Configuration;

final class ConfigurationLoader
{
    public static function loadConfiguration(): ?Configuration
    {
        return new Configuration(
          [
              /*
               * TODO: Episodes 401 - 449 can be downloaded manually, they are not in the feed anymore
               *
               * Hardwell On Air Official Podcast
               * https://podcasts.apple.com/de/podcast/hardwell-on-air-official-podcast/id559788668
               */
              new Subscription("Hardwell On Air", "http://podcast.djhardwell.com/podcast.xml", false),

              /*
               * ZEIT Verbrechen
               * https://podcasts.apple.com/de/podcast/verbrechen/id1374777077
               */
              new Subscription("ZEIT Verbrechen", "https://verbrechen.podigee.io/feed/mp3", false),

              /*
               * W&W Rave Culture Radio
               * https://podcasts.apple.com/de/podcast/w-w-rave-culture-radio/id443158849
               */
              new Subscription("W & W Rave Culture", "http://podcast.wandwmusic.nl/podcast.php", true),

              /*
               * TODO: Download-Grenze setzen! Bis 200 ist heruntergeladen.
               *
               * Dannic presents Fonk Radio
               * https://podcasts.apple.com/us/podcast/dannic-presents-fonk-radio/id682757084
               */
              new Subscription("Dannic presents Fonk Radio", "http://portal-api.thisisdistorted.com/xml/dannic-presents-front-of-house-radio", true),

              /*
               * CLUBLIFE by Tiësto
               * https://podcasts.apple.com/de/podcast/clublife/id251507798
               *
               * https://feeds.acast.com/public/shows/593eded1acfa040562f3480b
               */
              new Subscription("CLUBLIFE", "https://feeds.acast.com/public/shows/593eded1acfa040562f3480b", true),

              /*
               * The Martin Garrix Show
               * https://podcasts.apple.com/us/podcast/the-martin-garrix-show/id1132914986
               *
               * David Guetta: Playlist
               * Steve Aoki: Aoki’s House
               * A State Of Trance
               *
               * Maurice West presents: EUFORIKA (Bei iTunes weit hinterher)
               * Revealed Radio
               */
          ],
            __DIR__ . "/../../library"
        );
    }
}
