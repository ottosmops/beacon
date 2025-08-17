#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use BeaconParser\BeaconValidator;

/**
 * BEACON File Validator CLI Tool
 * 
 * Usage: php validate-beacon.php <file.beacon|url>
 */

/**
 * Check if the given string is a URL
 */
function isUrl(string $input): bool
{
    return filter_var($input, FILTER_VALIDATE_URL) !== false && 
           (str_starts_with($input, 'http://') || str_starts_with($input, 'https://'));
}

/**
 * Download content from URL
 */
function downloadFromUrl(string $url): string
{
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'BEACON-Parser-Validator/1.0',
            'method' => 'GET',
            'header' => "Accept: text/plain, text/*, */*\r\n",
            'ignore_errors' => true  // Allow reading error responses
        ]
    ]);
    
    $content = @file_get_contents($url, false, $context);
    
    if ($content === false) {
        throw new Exception("Failed to download content from URL: $url");
    }
    
    // Check HTTP response headers for status codes
    if (isset($http_response_header)) {
        $statusLine = $http_response_header[0] ?? '';
        if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $statusLine, $matches)) {
            $statusCode = (int)$matches[1];
            if ($statusCode >= 400) {
                throw new Exception("HTTP error $statusCode when downloading from URL: $url");
            }
        }
    }
    
    return $content;
}

if ($argc < 2) {
    echo "BEACON File Validator\n";
    echo "====================\n\n";
    echo "Usage: php validate-beacon.php <beacon-file-or-url>\n";
    echo "       php validate-beacon.php --help\n\n";
    echo "Examples:\n";
    echo "  php validate-beacon.php example.beacon\n";
    echo "  php validate-beacon.php /path/to/file.txt\n";
    echo "  php validate-beacon.php https://example.org/beacon.txt\n\n";
    exit(1);
}

if ($argv[1] === '--help' || $argv[1] === '-h') {
    echo "BEACON File Validator\n";
    echo "====================\n\n";
    echo "This tool validates BEACON files according to the BEACON specification.\n";
    echo "It supports both local files and URLs.\n\n";
    echo "It checks for:\n";
    echo "- Correct file structure and format\n";
    echo "- Valid meta fields and values\n";
    echo "- Proper link construction\n";
    echo "- URI validity\n";
    echo "- Best practices compliance\n\n";
    echo "Usage:\n";
    echo "  php validate-beacon.php <file-path>\n";
    echo "  php validate-beacon.php <url>\n\n";
    echo "Examples:\n";
    echo "  php validate-beacon.php example.beacon\n";
    echo "  php validate-beacon.php /path/to/file.txt\n";
    echo "  php validate-beacon.php https://example.org/beacon.txt\n\n";
    echo "Exit codes:\n";
    echo "  0 - File is valid\n";
    echo "  1 - File has errors\n";
    echo "  2 - File not found or other error\n\n";
    exit(0);
}

$input = $argv[1];

try {
    $validator = new BeaconValidator();
    
    if (isUrl($input)) {
        echo "Validating BEACON file from URL: $input\n";
        echo str_repeat("=", 50) . "\n\n";
        
        $content = downloadFromUrl($input);
        $result = $validator->validateString($content);
    } else {
        echo "Validating BEACON file: $input\n";
        echo str_repeat("=", 50) . "\n\n";
        
        $result = $validator->validateFile($input);
    }
    
    echo $result->getDetailedReport();
    
    // Exit with appropriate code
    if (!$result->isValid()) {
        exit(1);
    }
    
    exit(0);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(2);
}
