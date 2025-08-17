<?php

require_once 'vendor/autoload.php';

use BeaconParser\BeaconParser;
use BeaconParser\LinkConstructor;

$parser = new BeaconParser();
$data = $parser->parseFile('test-url.beacon');

echo "Meta fields:\n";
foreach ($data->getMetaFields() as $key => $value) {
    echo "  $key: $value\n";
}

echo "\nRaw links:\n";
foreach ($data->getRawLinks() as $link) {
    echo "  Source: " . $link->getSourceToken() . " | Target: " . $link->getTargetToken() . "\n";
}

$constructor = new LinkConstructor($data->getMetaFields());

echo "\nConstructed links:\n";
foreach ($data->getRawLinks() as $rawLink) {
    $link = $constructor->constructLink($rawLink);
    echo "  Source: " . $link->getSourceIdentifier() . " | Target: " . $link->getTargetIdentifier() . "\n";
}
