<?php

/*
 * This helper tool extracts the underlying feed URL for a provided iTunes Podcast URL.
 */

$url = "https://podcasts.apple.com/us/podcast/w-w-rave-culture-radio/id443158849";

//
// ----- Do not modify below this line -----
//

printf("[i] Received URL %s ." . PHP_EOL, $url);

/*
 * Search for "/id(\d+)" in the URL
 */
$matches = [];
if (1 !== preg_match("!/id(\d+)!", $url, $matches, PREG_UNMATCHED_AS_NULL)) {
   printf("[x] Did not find exactly one ID in the provided URL." . PHP_EOL);
   die();
}

// Ensured by regex/capture group
assert(isset($matches[1]) && is_numeric($matches[1]));

$id = $matches[1];

printf("[i] Extraced ID %d ." . PHP_EOL, $id);

/*
 * Query the iTunes API
 */

// Concatenate query string
$query = sprintf("https://itunes.apple.com/lookup?id=%d", $id);

printf("[i] Querying iTunes API using %s ." . PHP_EOL, $query);

$response = @file_get_contents($query);
if (null === $response) {
   printf("[x] Error occurred on query." . PHP_EOL);
   die();
}

// Parse response JSON
$json = @json_decode($response, true);
if (null === $json) {
   printf("[x] The response is invalid." . PHP_EOL);
   die();
}

if (!isset($json["resultCount"]) || $json["resultCount"] !== 1) {
   printf("[x] Result count is not as expected.");
}

// Results exists, is an array and has one item
assert(isset($json["results"]) && is_array($json["results"]) && isset($json["results"][0]));

// Furthermore, there must exist an "feedUrl" entry
assert(isset($json["results"][0]["feedUrl"]));

$feedUrl = $json["results"][0]["feedUrl"];

printf("[i] Success. Extracted feed URL: %s ." . PHP_EOL, $feedUrl);
