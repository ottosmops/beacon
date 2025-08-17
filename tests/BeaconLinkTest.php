<?php

declare(strict_types=1);

namespace BeaconParser\Tests;

use BeaconParser\BeaconLink;
use PHPUnit\Framework\TestCase;

class BeaconLinkTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $link = new BeaconLink(
            'http://example.org/source',
            'http://example.com/target',
            'http://www.w3.org/2000/01/rdf-schema#seeAlso',
            'test annotation'
        );

        $this->assertEquals('http://example.org/source', $link->getSourceIdentifier());
        $this->assertEquals('http://example.com/target', $link->getTargetIdentifier());
        $this->assertEquals('http://www.w3.org/2000/01/rdf-schema#seeAlso', $link->getRelationType());
        $this->assertEquals('test annotation', $link->getAnnotation());
        $this->assertTrue($link->hasAnnotation());
    }

    public function testLinkWithoutAnnotation(): void
    {
        $link = new BeaconLink(
            'http://example.org/source',
            'http://example.com/target',
            'http://www.w3.org/2000/01/rdf-schema#seeAlso'
        );

        $this->assertNull($link->getAnnotation());
        $this->assertFalse($link->hasAnnotation());
    }

    public function testLinkWithEmptyAnnotation(): void
    {
        $link = new BeaconLink(
            'http://example.org/source',
            'http://example.com/target',
            'http://www.w3.org/2000/01/rdf-schema#seeAlso',
            ''
        );

        $this->assertEquals('', $link->getAnnotation());
        $this->assertFalse($link->hasAnnotation());
    }

    public function testToArray(): void
    {
        $link = new BeaconLink(
            'http://example.org/source',
            'http://example.com/target',
            'http://www.w3.org/2000/01/rdf-schema#seeAlso',
            'test annotation'
        );

        $expected = [
            'sourceIdentifier' => 'http://example.org/source',
            'targetIdentifier' => 'http://example.com/target',
            'relationType' => 'http://www.w3.org/2000/01/rdf-schema#seeAlso',
            'annotation' => 'test annotation',
        ];

        $this->assertEquals($expected, $link->toArray());
    }
}
