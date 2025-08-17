<?php

require_once 'vendor/autoload.php';

use BeaconParser\BeaconValidator;

$validator = new BeaconValidator();

$content = "#FORMAT: BEACON\n" .
          "#PREFIX: http://example.org/\n" .
          "#TimeStamp: 2025-08-11T06:32:38+02:00\n" .
          "#target: http://example.com/{ID}\n" .
          "#description: Test file\n" .
          "alice|alice\n";

$result = $validator->validateString($content);

echo "Valid: " . ($result->isValid() ? 'true' : 'false') . "\n";
echo "Errors: " . count($result->getErrors()) . "\n";
echo "Warnings: " . count($result->getWarnings()) . "\n";

echo "\nWarnings:\n";
foreach ($result->getWarnings() as $warning) {
    echo "  - $warning\n";
}
