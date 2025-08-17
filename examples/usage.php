<?php

require_once __DIR__ . '/../vendor/autoload.php';

use BeaconParser\BeaconParser;

// Example usage of the BEACON Parser

echo "BEACON Parser Example\n";
echo "=====================\n\n";

try {
    $parser = new BeaconParser();
    
    // Parse the example BEACON file
    $beaconData = $parser->parseFile(__DIR__ . '/example.beacon');
    
    echo "Meta Fields:\n";
    echo "------------\n";
    foreach ($beaconData->getMetaFields() as $field => $value) {
        echo "$field: $value\n";
    }
    
    echo "\nRaw Links:\n";
    echo "----------\n";
    foreach ($beaconData->getRawLinks() as $i => $rawLink) {
        echo ($i + 1) . ". Source: " . $rawLink->getSourceToken();
        if ($rawLink->hasAnnotationToken()) {
            echo " | Annotation: " . $rawLink->getAnnotationToken();
        }
        if ($rawLink->hasTargetToken()) {
            echo " | Target: " . $rawLink->getTargetToken();
        }
        echo "\n";
    }
    
    echo "\nConstructed Links:\n";
    echo "------------------\n";
    foreach ($beaconData->getConstructedLinks() as $i => $link) {
        echo ($i + 1) . ". " . $link->getSourceIdentifier() . 
             " --[" . basename($link->getRelationType()) . "]--> " . 
             $link->getTargetIdentifier();
        if ($link->hasAnnotation()) {
            echo " (\"" . $link->getAnnotation() . "\")";
        }
        echo "\n";
    }
    
    echo "\nTotal Links: " . $beaconData->getLinkCount() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
