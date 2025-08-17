<?php

declare(strict_types=1);

namespace BeaconParser;

/**
 * Represents a link in a BEACON file
 *
 * A link consists of a source identifier, target identifier,
 * relation type, and optional annotation.
 */
class BeaconLink
{
    private string $sourceIdentifier;
    private string $targetIdentifier;
    private string $relationType;
    private ?string $annotation;

    public function __construct(
        string $sourceIdentifier,
        string $targetIdentifier,
        string $relationType,
        ?string $annotation = null
    ) {
        $this->sourceIdentifier = $sourceIdentifier;
        $this->targetIdentifier = $targetIdentifier;
        $this->relationType = $relationType;
        $this->annotation = $annotation;
    }

    /**
     * Get the source identifier (where the link points from)
     */
    public function getSourceIdentifier(): string
    {
        return $this->sourceIdentifier;
    }

    /**
     * Get the target identifier (where the link points to)
     */
    public function getTargetIdentifier(): string
    {
        return $this->targetIdentifier;
    }

    /**
     * Get the relation type of the link
     */
    public function getRelationType(): string
    {
        return $this->relationType;
    }

    /**
     * Get the link annotation (optional additional description)
     */
    public function getAnnotation(): ?string
    {
        return $this->annotation;
    }

    /**
     * Check if the link has an annotation
     */
    public function hasAnnotation(): bool
    {
        return $this->annotation !== null && $this->annotation !== '';
    }

    /**
     * Convert link to array representation
     *
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'sourceIdentifier' => $this->sourceIdentifier,
            'targetIdentifier' => $this->targetIdentifier,
            'relationType' => $this->relationType,
            'annotation' => $this->annotation,
        ];
    }
}
