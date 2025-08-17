<?php

require_once 'vendor/autoload.php';

use BeaconParser\BeaconValidator;

$validator = new BeaconValidator();

$content = "#FORMAT: BEACON\n" .
          "#PREFIX: http://example.org/\n" .
          "#TIMESTAMP: 2025-08-11T06:32:38+02:00\n" .
          "alice|http://example.com/alice\n";

$result = $validator->validateString($content);

echo "Valid: " . ($result->isValid() ? 'true' : 'false') . "\n";
echo "Errors:\n";
foreach ($result->getErrors() as $error) {
    echo "  - $error\n";
}

// Test just the DateTime creation
echo "\nDirect DateTime test:\n";
try {
    $date = new DateTime("2025-08-11T06:32:38+02:00");
    echo "Success: " . $date->format('c') . "\n";
} catch (Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n";
}
