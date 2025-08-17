<?php

declare(strict_types=1);

namespace BeaconParser;

use BeaconParser\Exception\BeaconParseException;
use DateTime;
use Exception;

/**
 * Comprehensive validator for BEACON files
 *
 * Validates BEACON files according to the specification and provides
 * detailed feedback about errors, warnings, and best practices.
 */
class BeaconValidator
{
    // Known meta fields according to BEACON specification
    private const KNOWN_META_FIELDS = [
        // Link construction fields
        'PREFIX', 'TARGET', 'MESSAGE', 'RELATION', 'ANNOTATION',
        // Link dump description fields
        'DESCRIPTION', 'CREATOR', 'CONTACT', 'HOMEPAGE', 'FEED', 'TIMESTAMP', 'UPDATE',
        // Dataset fields
        'SOURCESET', 'TARGETSET', 'NAME', 'INSTITUTION'
    ];

    // Valid UPDATE field values
    private const VALID_UPDATE_VALUES = [
        'always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'
    ];

    private BeaconParser $parser;

    public function __construct()
    {
        $this->parser = new BeaconParser();
    }

    /**
     * Validate a BEACON file from file path
     */
    public function validateFile(string $filePath): BeaconValidationResult
    {
        $result = new BeaconValidationResult();

        if (!file_exists($filePath)) {
            $result->addError("File not found: $filePath");
            return $result;
        }

        if (!is_readable($filePath)) {
            $result->addError("File is not readable: $filePath");
            return $result;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            $result->addError("Could not read file: $filePath");
            return $result;
        }

        return $this->validateString($content);
    }

    /**
     * Validate a BEACON file from string content
     */
    public function validateString(string $content): BeaconValidationResult
    {
        $result = new BeaconValidationResult();

        try {
            // First, try to parse the file
            $beaconData = $this->parser->parseString($content);

            // Perform detailed validation
            $this->validateStructure($content, $result);
            $this->validateMetaFields($beaconData, $result);
            $this->validateLinks($beaconData, $result);
            $this->validateBestPractices($beaconData, $result);
        } catch (BeaconParseException $e) {
            $result->addError("Parse error: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Validate the basic structure of the BEACON file
     */
    private function validateStructure(string $content, BeaconValidationResult $result): void
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $content));

        // Check for UTF-8 BOM
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $result->addInfo("File contains UTF-8 BOM");
        }

        // Check for FORMAT line
        $hasFormatLine = false;
        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^#FORMAT\s*:\s*BEACON\s*$/i', $line)) {
                $hasFormatLine = true;
                if ($lineNum > 5) {
                    $result->addWarning(
                        "FORMAT line found at line " . ($lineNum + 1) . " - should be near the beginning"
                    );
                }
                break;
            }

            // Stop checking after first 10 lines for format
            if ($lineNum > 10) {
                break;
            }
        }

        if (!$hasFormatLine) {
            $result->addWarning("No #FORMAT: BEACON line found - recommended for BEACON files");
        }

        // Check for non-compliant meta field names (not A-Z only)
        $this->validateMetaFieldNaming($content, $result);

        // Check file ending
        if (!str_ends_with($content, "\n")) {
            $result->addWarning("File should end with a line break");
        }
    }

    /**
     * Validate meta field naming according to BEACON specification
     */
    private function validateMetaFieldNaming(string $content, BeaconValidationResult $result): void
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $content));

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);

            // Check for meta field lines
            if (preg_match('/^#([A-Za-z]+)\s*:\s*/', $line, $matches)) {
                $fieldName = $matches[1];

                // Check if field name contains lowercase letters (not BEACON compliant)
                if (!preg_match('/^[A-Z]+$/', $fieldName)) {
                    $result->addWarning(
                        "Meta field '#$fieldName:' on line " . ($lineNum + 1) .
                        " uses non-standard casing. " .
                        "BEACON specification requires uppercase A-Z only (METAFIELD = +( %x41-5A ))"
                    );
                }
            }
        }
    }

    /**
     * Validate meta fields
     */
    private function validateMetaFields(BeaconData $beaconData, BeaconValidationResult $result): void
    {
        $metaFields = $beaconData->getMetaFields();

        // Check for unknown meta fields
        foreach ($metaFields as $field => $value) {
            if (!in_array($field, self::KNOWN_META_FIELDS, true)) {
                $result->addWarning("Unknown meta field: $field");
            }
        }

        // Validate specific meta fields
        $this->validateUriFields($metaFields, $result);
        $this->validateTimestampField($metaFields, $result);
        $this->validateUpdateField($metaFields, $result);
        $this->validateContactField($metaFields, $result);

        // Check for recommended meta fields
        $this->checkRecommendedFields($metaFields, $result);
    }

    /**
     * Validate URI-related meta fields
     * @param array<string, string> $metaFields
     */
    private function validateUriFields(array $metaFields, BeaconValidationResult $result): void
    {
        $uriFields = ['HOMEPAGE', 'FEED', 'SOURCESET', 'TARGETSET'];

        foreach ($uriFields as $field) {
            if (isset($metaFields[$field])) {
                $value = $metaFields[$field];
                if (!$this->isValidUri($value)) {
                    $result->addError("Invalid URI in $field: $value");
                }
            }
        }

        // Validate RELATION field
        if (isset($metaFields['RELATION'])) {
            $relation = $metaFields['RELATION'];
            if (!$this->isValidUri($relation) && !$this->isUriPattern($relation)) {
                $result->addError("RELATION must be a valid URI or URI pattern: $relation");
            }
        }
    }

    /**
     * Validate TIMESTAMP field
     * @param array<string, string> $metaFields
     */
    private function validateTimestampField(array $metaFields, BeaconValidationResult $result): void
    {
        if (!isset($metaFields['TIMESTAMP'])) {
            return;
        }

        $timestamp = $metaFields['TIMESTAMP'];

        // Try to parse as RFC3339 date or datetime
        $datePatterns = [
            '/^\d{4}-\d{2}-\d{2}$/',  // YYYY-MM-DD
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',  // Full datetime with timezone
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/',  // Full datetime with Z
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/',  // Local datetime
        ];

        $isValid = false;
        foreach ($datePatterns as $pattern) {
            if (preg_match($pattern, $timestamp)) {
                // Additional validation: try to create DateTime to verify validity
                try {
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $timestamp)) {
                        new DateTime($timestamp);
                    } else {
                        new DateTime($timestamp);
                    }
                    $isValid = true;
                    break;
                } catch (Exception $e) {
                    // Continue to next pattern
                }
            }
        }

        if (!$isValid) {
            $result->addError("Invalid TIMESTAMP format. Expected ISO 8601 date/datetime: $timestamp");
        }
    }

    /**
     * Validate UPDATE field
     * @param array<string, string> $metaFields
     */
    private function validateUpdateField(array $metaFields, BeaconValidationResult $result): void
    {
        if (!isset($metaFields['UPDATE'])) {
            return;
        }

        $update = $metaFields['UPDATE'];
        if (!in_array($update, self::VALID_UPDATE_VALUES, true)) {
            $result->addError(
                "Invalid UPDATE value: $update (must be one of: " . implode(', ', self::VALID_UPDATE_VALUES) . ")"
            );
        }
    }

    /**
     * Validate CONTACT field
     * @param array<string, string> $metaFields
     */
    private function validateContactField(array $metaFields, BeaconValidationResult $result): void
    {
        if (!isset($metaFields['CONTACT'])) {
            return;
        }

        $contact = $metaFields['CONTACT'];

        // Simple email validation
        if (!filter_var($contact, FILTER_VALIDATE_EMAIL) && !str_contains($contact, '@')) {
            $result->addWarning("CONTACT field should contain a valid email address: $contact");
        }
    }

    /**
     * Check for recommended meta fields
     * @param array<string, string> $metaFields
     */
    private function checkRecommendedFields(array $metaFields, BeaconValidationResult $result): void
    {
        $recommendedFields = ['DESCRIPTION', 'CREATOR'];

        foreach ($recommendedFields as $field) {
            if (!isset($metaFields[$field])) {
                $result->addInfo("Recommended meta field missing: $field");
            }
        }
    }

    /**
     * Validate links
     */
    private function validateLinks(BeaconData $beaconData, BeaconValidationResult $result): void
    {
        $rawLinks = $beaconData->getRawLinks();
        $constructedLinks = $beaconData->getConstructedLinks();

        if (empty($rawLinks)) {
            $result->addWarning("No links found in BEACON file");
            return;
        }

        // Check for duplicate links
        $this->checkDuplicateLinks($constructedLinks, $result);

        // Validate constructed URIs
        $this->validateConstructedUris($constructedLinks, $result);

        $result->addInfo("Total links: " . count($rawLinks));
    }

    /**
     * Check for duplicate links
     * @param array<BeaconLink> $links
     */
    private function checkDuplicateLinks(array $links, BeaconValidationResult $result): void
    {
        $seen = [];
        $duplicates = 0;

        foreach ($links as $link) {
            $key = $link->getSourceIdentifier() . '|' . $link->getTargetIdentifier() . '|' . $link->getRelationType();

            if (isset($seen[$key])) {
                $duplicates++;
            } else {
                $seen[$key] = true;
            }
        }

        if ($duplicates > 0) {
            $result->addWarning("Found $duplicates duplicate links");
        }
    }

    /**
     * Validate constructed URIs
     * @param array<BeaconLink> $links
     */
    private function validateConstructedUris(array $links, BeaconValidationResult $result): void
    {
        $invalidUris = 0;

        foreach ($links as $link) {
            if (!$this->isValidUri($link->getSourceIdentifier())) {
                $invalidUris++;
            }
            if (!$this->isValidUri($link->getTargetIdentifier())) {
                $invalidUris++;
            }
            if (!$this->isValidUri($link->getRelationType())) {
                $invalidUris++;
            }
        }

        if ($invalidUris > 0) {
            $result->addError("Found $invalidUris invalid URIs in constructed links");
        }
    }

    /**
     * Validate best practices
     */
    private function validateBestPractices(BeaconData $beaconData, BeaconValidationResult $result): void
    {
        $metaFields = $beaconData->getMetaFields();

        // Check for MIME type recommendation
        $result->addInfo("Recommended MIME type: text/plain");
        $result->addInfo("Recommended file extension: .txt");

        // Check if using HTTPS URIs
        $httpUris = 0;
        foreach ($metaFields as $field => $value) {
            if (in_array($field, ['HOMEPAGE', 'FEED', 'SOURCESET', 'TARGETSET'], true)) {
                if (str_starts_with($value, 'http://')) {
                    $httpUris++;
                }
            }
        }

        if ($httpUris > 0) {
            $result->addInfo("Consider using HTTPS instead of HTTP for security");
        }
    }

    /**
     * Check if a string is a valid URI
     */
    private function isValidUri(string $uri): bool
    {
        return filter_var($uri, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check if a string is a URI pattern
     */
    private function isUriPattern(string $value): bool
    {
        return str_contains($value, '{ID}') || str_contains($value, '{+ID}');
    }
}
