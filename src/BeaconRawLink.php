<?php

declare(strict_types=1);

namespace BeaconParser;

/**
 * Represents a raw link token from a BEACON file before construction
 *
 * Raw links contain the tokens as they appear in the file,
 * before URI pattern expansion and identifier construction.
 */
class BeaconRawLink
{
    private string $sourceToken;
    private ?string $annotationToken;
    private ?string $targetToken;

    public function __construct(
        string $sourceToken,
        ?string $annotationToken = null,
        ?string $targetToken = null
    ) {
        $this->sourceToken = $sourceToken;
        $this->annotationToken = $annotationToken;
        $this->targetToken = $targetToken;
    }

    /**
     * Get the source token
     */
    public function getSourceToken(): string
    {
        return $this->sourceToken;
    }

    /**
     * Get the annotation token
     */
    public function getAnnotationToken(): ?string
    {
        return $this->annotationToken;
    }

    /**
     * Get the target token
     */
    public function getTargetToken(): ?string
    {
        return $this->targetToken;
    }

    /**
     * Check if the link has an annotation token
     */
    public function hasAnnotationToken(): bool
    {
        return $this->annotationToken !== null && $this->annotationToken !== '';
    }

    /**
     * Check if the link has a target token
     */
    public function hasTargetToken(): bool
    {
        return $this->targetToken !== null && $this->targetToken !== '';
    }

    /**
     * Convert raw link to array representation
     *
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'sourceToken' => $this->sourceToken,
            'annotationToken' => $this->annotationToken,
            'targetToken' => $this->targetToken,
        ];
    }
}
