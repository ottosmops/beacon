<?php

declare(strict_types=1);

namespace BeaconParser;

/**
 * Represents the complete data from a parsed BEACON file
 *
 * Contains meta fields and links extracted from a BEACON file.
 */
class BeaconData
{
    /** @var array<string, string> */
    private array $metaFields;

    /** @var BeaconRawLink[] */
    private array $rawLinks;

    /**
     * @param array<string, string> $metaFields
     * @param BeaconRawLink[] $rawLinks
     */
    public function __construct(array $metaFields = [], array $rawLinks = [])
    {
        $this->metaFields = $metaFields;
        $this->rawLinks = $rawLinks;
    }

    /**
     * Get a meta field value by name
     */
    public function getMetaField(string $name): ?string
    {
        return $this->metaFields[$name] ?? null;
    }

    /**
     * Get all meta fields
     *
     * @return array<string, string>
     */
    public function getMetaFields(): array
    {
        return $this->metaFields;
    }

    /**
     * Set a meta field value
     */
    public function setMetaField(string $name, string $value): void
    {
        $this->metaFields[$name] = $value;
    }

    /**
     * Check if a meta field exists
     */
    public function hasMetaField(string $name): bool
    {
        return isset($this->metaFields[$name]);
    }

    /**
     * Get raw links (tokens as they appear in the file)
     *
     * @return BeaconRawLink[]
     */
    public function getRawLinks(): array
    {
        return $this->rawLinks;
    }

    /**
     * Add a raw link
     */
    public function addRawLink(BeaconRawLink $link): void
    {
        $this->rawLinks[] = $link;
    }

    /**
     * Get the number of links
     */
    public function getLinkCount(): int
    {
        return count($this->rawLinks);
    }

    /**
     * Get constructed links with full URIs
     *
     * @return BeaconLink[]
     */
    public function getConstructedLinks(): array
    {
        $constructor = new LinkConstructor($this->metaFields);
        $links = [];

        foreach ($this->rawLinks as $rawLink) {
            $links[] = $constructor->constructLink($rawLink);
        }

        return $links;
    }

    /**
     * Convert to array representation
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'metaFields' => $this->metaFields,
            'rawLinks' => array_map(fn($link) => $link->toArray(), $this->rawLinks),
            'constructedLinks' => array_map(fn($link) => $link->toArray(), $this->getConstructedLinks()),
        ];
    }
}
