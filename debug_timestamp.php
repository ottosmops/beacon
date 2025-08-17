<?php

$timestamp = "2025-08-11T06:32:38+02:00";

$pattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/';

if (preg_match($pattern, $timestamp)) {
    echo "Pattern matches!\n";
    
    try {
        $date = new DateTime($timestamp);
        echo "DateTime creation successful: " . $date->format('c') . "\n";
    } catch (Exception $e) {
        echo "DateTime creation failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "Pattern does not match.\n";
}

// Test all patterns
$patterns = [
    '/^\d{4}-\d{2}-\d{2}$/',  // YYYY-MM-DD
    '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',  // Full datetime with timezone
    '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/',  // Full datetime with Z
    '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/',  // Local datetime
];

echo "\nTesting all patterns:\n";
foreach ($patterns as $i => $pattern) {
    if (preg_match($pattern, $timestamp)) {
        echo "Pattern $i matches: $pattern\n";
    }
}
