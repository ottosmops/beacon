<?php

declare(strict_types=1);

namespace BeaconParser\Tests;

use BeaconParser\BeaconRawLink;
use BeaconParser\LinkConstructor;
use PHPUnit\Framework\TestCase;

class LinkConstructorTest extends TestCase
{
    public function testConstructWithDefaultValues(): void
    {
        $metaFields = [];
        $constructor = new LinkConstructor($metaFields);
        $rawLink = new BeaconRawLink('alice');

        $link = $constructor->constructLink($rawLink);

        $this->assertEquals('alice', $link->getSourceIdentifier());
        $this->assertEquals('alice', $link->getTargetIdentifier());
        $this->assertEquals('http://www.w3.org/2000/01/rdf-schema#seeAlso', $link->getRelationType());
        $this->assertNull($link->getAnnotation());
    }

    public function testConstructWithPrefixAndTarget(): void
    {
        $metaFields = [
            'PREFIX' => 'http://example.org/',
            'TARGET' => 'http://example.com/',
        ];
        $constructor = new LinkConstructor($metaFields);
        $rawLink = new BeaconRawLink('alice');

        $link = $constructor->constructLink($rawLink);

        $this->assertEquals('http://example.org/alice', $link->getSourceIdentifier());
        $this->assertEquals('http://example.com/alice', $link->getTargetIdentifier());
    }

    public function testConstructWithUriPatterns(): void
    {
        $metaFields = [
            'PREFIX' => 'http://example.org/{ID}',
            'TARGET' => 'http://example.com/{+ID}',
        ];
        $constructor = new LinkConstructor($metaFields);
        $rawLink = new BeaconRawLink('hello world');

        $link = $constructor->constructLink($rawLink);

        $this->assertEquals('http://example.org/hello%20world', $link->getSourceIdentifier());
        $this->assertEquals('http://example.com/hello world', $link->getTargetIdentifier());
    }

    public function testConstructWithTargetToken(): void
    {
        $metaFields = [
            'PREFIX' => 'http://example.org/',
            'TARGET' => 'http://example.com/',
        ];
        $constructor = new LinkConstructor($metaFields);
        $rawLink = new BeaconRawLink('alice', null, 'target123');

        $link = $constructor->constructLink($rawLink);

        $this->assertEquals('http://example.org/alice', $link->getSourceIdentifier());
        $this->assertEquals('http://example.com/target123', $link->getTargetIdentifier());
    }

    public function testConstructWithAnnotation(): void
    {
        $metaFields = [
            'PREFIX' => 'http://example.org/',
            'TARGET' => 'http://example.com/',
            'RELATION' => 'http://www.w3.org/2000/01/rdf-schema#seeAlso',
        ];
        $constructor = new LinkConstructor($metaFields);
        $rawLink = new BeaconRawLink('alice', 'test annotation');

        $link = $constructor->constructLink($rawLink);

        $this->assertEquals('test annotation', $link->getAnnotation());
    }

    public function testConstructWithMessageField(): void
    {
        $metaFields = [
            'PREFIX' => 'http://example.org/',
            'TARGET' => 'http://example.com/',
            'MESSAGE' => 'Default message',
        ];
        $constructor = new LinkConstructor($metaFields);
        $rawLink = new BeaconRawLink('alice');

        $link = $constructor->constructLink($rawLink);

        $this->assertEquals('Default message', $link->getAnnotation());
    }

    public function testConstructWithRelationPattern(): void
    {
        $metaFields = [
            'PREFIX' => 'http://example.org/',
            'TARGET' => 'http://example.com/',
            'RELATION' => 'http://example.org/relation/{ID}',
        ];
        $constructor = new LinkConstructor($metaFields);
        $rawLink = new BeaconRawLink('alice', 'test');

        $link = $constructor->constructLink($rawLink);

        $this->assertEquals('http://example.org/relation/test', $link->getRelationType());
    }

    public function testNoPatternAppendsId(): void
    {
        $metaFields = [
            'PREFIX' => 'http://example.org/',
            'TARGET' => 'http://example.com/',
        ];
        $constructor = new LinkConstructor($metaFields);
        $rawLink = new BeaconRawLink('hello world');

        $link = $constructor->constructLink($rawLink);

        $this->assertEquals('http://example.org/hello%20world', $link->getSourceIdentifier());
        $this->assertEquals('http://example.com/hello%20world', $link->getTargetIdentifier());
    }
}
