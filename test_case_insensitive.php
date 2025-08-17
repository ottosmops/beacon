<?php

require_once 'vendor/autoload.php';

use BeaconParser\BeaconParser;

// Test case-insensitive meta fields
$content = "#FORMAT: BEACON\n" .
          "#PREFIX: http://example.org/\n" .
          "#TimeStamp: 2025-08-11T06:32:38+02:00\n" .
          "#target: http://example.com/{ID}\n" .
          "alice|alice\n";

$parser = new BeaconParser();
$beaconData = $parser->parseString($content);

echo "Meta fields:\n";
foreach ($beaconData->getMetaFields() as $key => $value) {
    echo "  $key: $value\n";
}

echo "\nRaw links:\n";
foreach ($beaconData->getRawLinks() as $link) {
    echo "  Source: " . $link->getSourceToken() . " | Target: " . $link->getTargetToken() . "\n";
}
