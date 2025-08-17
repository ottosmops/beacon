<?php

declare(strict_types=1);

namespace BeaconParser;

use BeaconParser\Exception\BeaconParseException;

/**
 * Main parser class for BEACON files
 *
 * Parses BEACON files according to the specification at:
 * https://gbv.github.io/beaconspec/beacon.html
 */
class BeaconParser
{
    /**
     * Parse a BEACON file from a file path
     *
     * @throws BeaconParseException
     */
    public function parseFile(string $filePath): BeaconData
    {
        if (!file_exists($filePath)) {
            throw new BeaconParseException("File not found: $filePath");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new BeaconParseException("Could not read file: $filePath");
        }

        return $this->parseString($content);
    }

    /**
     * Parse a BEACON file from a string
     *
     * @throws BeaconParseException
     */
    public function parseString(string $content): BeaconData
    {
        // Remove Unicode BOM if present
        $content = $this->removeBom($content);

        // Split into lines
        $lines = $this->splitLines($content);

        $beaconData = new BeaconData();
        $inLinkSection = false;

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);

            // Skip empty lines
            if ($line === '') {
                $inLinkSection = true;
                continue;
            }

            // Check for format indicator
            if ($lineNumber === 0 && $this->isFormatLine($line)) {
                continue;
            }

            // Parse meta fields
            if ($this->isMetaLine($line)) {
                if ($inLinkSection) {
                    throw new BeaconParseException(
                        "Meta field found after link section on line " . ($lineNumber + 1)
                    );
                }
                $this->parseMetaLine($line, $beaconData, $lineNumber);
                continue;
            }

            // Check for invalid meta field format (starts with # and letters but wrong format)
            if ($this->looksLikeInvalidMetaField($line)) {
                throw new BeaconParseException(
                    "Invalid meta field format on line " . ($lineNumber + 1) . ": $line"
                );
            }

            // Parse link lines
            if (!$this->isComment($line)) {
                $inLinkSection = true;
                $this->parseLinkLine($line, $beaconData, $lineNumber);
            }
        }

        return $beaconData;
    }

    /**
     * Remove Unicode Byte Order Mark (BOM) if present
     */
    private function removeBom(string $content): string
    {
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            return substr($content, 3);
        }
        return $content;
    }

    /**
     * Split content into lines, handling different line endings
     *
     * @return string[]
     */
    private function splitLines(string $content): array
    {
        // Normalize line endings
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        return explode("\n", $content);
    }

    /**
     * Check if line is a format indicator
     */
    private function isFormatLine(string $line): bool
    {
        return preg_match('/^#FORMAT\s*:\s*BEACON\s*$/i', $line) === 1;
    }

    /**
     * Check if line is a meta field line
     */
    private function isMetaLine(string $line): bool
    {
        return preg_match('/^#[A-Za-z]+\s*[:]\s*/', $line) === 1;
    }

    /**
     * Check if line is a comment (but not a meta field)
     */
    private function isComment(string $line): bool
    {
        return str_starts_with($line, '#') && !$this->isMetaLine($line);
    }

    /**
     * Check if line looks like an invalid meta field (starts with # and letters but wrong format)
     */
    private function looksLikeInvalidMetaField(string $line): bool
    {
        // Starts with # followed by letters, but doesn't match valid meta field format
        return preg_match('/^#[A-Za-z]/', $line) === 1 && !$this->isMetaLine($line);
    }

    /**
     * Parse a meta field line
     *
     * @throws BeaconParseException
     */
    private function parseMetaLine(string $line, BeaconData $beaconData, int $lineNumber): void
    {
        // Extract field name and value using regex (case-insensitive)
        if (!preg_match('/^#([A-Za-z]+)\s*:\s*(.*)$/', $line, $matches)) {
            throw new BeaconParseException(
                "Invalid meta field format on line " . ($lineNumber + 1) . ": $line"
            );
        }

        $fieldName = strtoupper($matches[1]); // Normalize to uppercase
        $fieldValue = trim($matches[2]);

        // Check for duplicate meta fields
        if ($beaconData->hasMetaField($fieldName)) {
            // According to spec, emit warning but ignore duplicate
            error_log("Warning: Duplicate meta field '$fieldName' on line " . ($lineNumber + 1));
            return;
        }

        $beaconData->setMetaField($fieldName, $fieldValue);
    }

    /**
     * Parse a link line
     *
     * @throws BeaconParseException
     */
    private function parseLinkLine(string $line, BeaconData $beaconData, int $lineNumber): void
    {
        // Skip lines with only whitespace
        if (trim($line) === '') {
            return;
        }

        // Split by vertical bar (|)
        $parts = explode('|', $line);

        if (count($parts) > 4) {
            throw new BeaconParseException(
                "Invalid link format on line " . ($lineNumber + 1) . ": $line"
            );
        }

        $sourceToken = $this->normalizeWhitespace(trim($parts[0]));

        // Source token must not be empty or only whitespace
        if ($sourceToken === '') {
            return; // Skip empty source tokens
        }

        $annotationToken = null;
        $targetToken = null;

        if (count($parts) === 2) {
            // Ambiguous case: could be annotation or target
            $secondToken = $this->normalizeWhitespace(trim($parts[1]));

            if ($this->shouldTreatAsTarget($secondToken, $beaconData)) {
                $targetToken = $secondToken;
            } else {
                $annotationToken = $secondToken;
            }
        } elseif (count($parts) >= 3) {
            $annotationToken = $this->normalizeWhitespace(trim($parts[1]));
            $targetToken = $this->normalizeWhitespace(trim($parts[2]));
        }

        // Convert empty strings to null
        $annotationToken = $annotationToken === '' ? null : $annotationToken;
        $targetToken = $targetToken === '' ? null : $targetToken;

        $rawLink = new BeaconRawLink($sourceToken, $annotationToken, $targetToken);
        $beaconData->addRawLink($rawLink);
    }

    /**
     * Determine if second token should be treated as target (vs annotation)
     * Based on BEACON spec rules
     */
    private function shouldTreatAsTarget(string $token, BeaconData $beaconData): bool
    {
        // If TARGET meta field has default value and token starts with http:/https:
        $targetField = $beaconData->getMetaField('TARGET') ?? '{+ID}';

        if (
            $targetField === '{+ID}' &&
            (str_starts_with($token, 'http://') || str_starts_with($token, 'https://'))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Normalize whitespace according to BEACON spec
     *
     * Strip leading/trailing whitespace and replace sequences with single space
     */
    private function normalizeWhitespace(string $text): string
    {
        // Replace all whitespace sequences with single space
        $text = preg_replace('/\s+/', ' ', $text);

        // Trim leading and trailing whitespace
        return $text !== null ? trim($text) : '';
    }
}
