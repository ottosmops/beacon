<?php

declare(strict_types=1);

namespace BeaconParser;

/**
 * Constructs full BeaconLink objects from raw link tokens and meta fields
 *
 * Handles URI pattern expansion and applies default values according
 * to the BEACON specification.
 */
class LinkConstructor
{
    /** @var array<string, string> */
    private array $metaFields;

    // Default meta field values according to BEACON specification
    private const DEFAULT_PREFIX = '{+ID}';
    private const DEFAULT_TARGET = '{+ID}';
    private const DEFAULT_MESSAGE = '';
    private const DEFAULT_RELATION = 'http://www.w3.org/2000/01/rdf-schema#seeAlso';

    /**
     * @param array<string, string> $metaFields
     */
    public function __construct(array $metaFields)
    {
        $this->metaFields = $metaFields;
    }

    /**
     * Construct a BeaconLink from a raw link token
     */
    public function constructLink(BeaconRawLink $rawLink): BeaconLink
    {
        $sourceIdentifier = $this->constructIdentifier(
            $this->getMetaFieldValue('PREFIX', self::DEFAULT_PREFIX),
            $rawLink->getSourceToken()
        );

        $targetIdentifier = $this->constructTargetIdentifier($rawLink);

        $relationType = $this->constructRelationType($rawLink);

        $annotation = $this->constructAnnotation($rawLink);

        return new BeaconLink(
            $sourceIdentifier,
            $targetIdentifier,
            $relationType,
            $annotation
        );
    }

    /**
     * Construct target identifier based on TARGET meta field and tokens
     */
    private function constructTargetIdentifier(BeaconRawLink $rawLink): string
    {
        $targetPattern = $this->getMetaFieldValue('TARGET', self::DEFAULT_TARGET);

        // Use target token if available and not empty, otherwise use source token
        $token = $rawLink->hasTargetToken()
            ? $rawLink->getTargetToken()
            : $rawLink->getSourceToken();

        return $this->constructIdentifier($targetPattern, $token ?? '');
    }

    /**
     * Construct relation type based on RELATION meta field and annotation token
     */
    private function constructRelationType(BeaconRawLink $rawLink): string
    {
        $relation = $this->getMetaFieldValue('RELATION', self::DEFAULT_RELATION);

        // If RELATION contains a URI pattern and we have an annotation token
        if ($this->isUriPattern($relation) && $rawLink->hasAnnotationToken()) {
            return $this->constructIdentifier($relation, $rawLink->getAnnotationToken() ?? '');
        }

        return $relation;
    }

    /**
     * Construct annotation based on MESSAGE meta field and annotation token
     */
    private function constructAnnotation(BeaconRawLink $rawLink): ?string
    {
        $relation = $this->getMetaFieldValue('RELATION', self::DEFAULT_RELATION);

        // If RELATION contains a URI and we have an annotation token, use the token
        if ($this->isUri($relation) && $rawLink->hasAnnotationToken()) {
            $annotation = $rawLink->getAnnotationToken();
            return $annotation !== '' ? $annotation : null;
        }

        // Otherwise use MESSAGE meta field
        $message = $this->getMetaFieldValue('MESSAGE', self::DEFAULT_MESSAGE);
        return $message !== '' ? $message : null;
    }

    /**
     * Construct an identifier from a URI pattern and token
     */
    private function constructIdentifier(string $pattern, string $token): string
    {
        // Handle {+ID} pattern (reserved expansion)
        if (str_contains($pattern, '{+ID}')) {
            return str_replace('{+ID}', $token, $pattern);
        }

        // Handle {ID} pattern (simple string expansion)
        if (str_contains($pattern, '{ID}')) {
            return str_replace('{ID}', rawurlencode($token), $pattern);
        }

        // If no pattern, append {ID} and use simple expansion
        return $pattern . rawurlencode($token);
    }

    /**
     * Get meta field value with fallback to default
     */
    private function getMetaFieldValue(string $field, string $default): string
    {
        return $this->metaFields[$field] ?? $default;
    }

    /**
     * Check if a string is a URI (contains ://)
     */
    private function isUri(string $value): bool
    {
        return str_contains($value, '://');
    }

    /**
     * Check if a string is a URI pattern (contains {ID} or {+ID})
     */
    private function isUriPattern(string $value): bool
    {
        return str_contains($value, '{ID}') || str_contains($value, '{+ID}');
    }
}
