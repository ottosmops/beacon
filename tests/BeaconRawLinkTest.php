<?php

declare(strict_types=1);

namespace BeaconParser\Tests;

use BeaconParser\BeaconRawLink;
use PHPUnit\Framework\TestCase;

class BeaconRawLinkTest extends TestCase
{
    public function testConstructorWithAllTokens(): void
    {
        $rawLink = new BeaconRawLink('source', 'annotation', 'target');

        $this->assertEquals('source', $rawLink->getSourceToken());
        $this->assertEquals('annotation', $rawLink->getAnnotationToken());
        $this->assertEquals('target', $rawLink->getTargetToken());
        $this->assertTrue($rawLink->hasAnnotationToken());
        $this->assertTrue($rawLink->hasTargetToken());
    }

    public function testConstructorWithSourceOnly(): void
    {
        $rawLink = new BeaconRawLink('source');

        $this->assertEquals('source', $rawLink->getSourceToken());
        $this->assertNull($rawLink->getAnnotationToken());
        $this->assertNull($rawLink->getTargetToken());
        $this->assertFalse($rawLink->hasAnnotationToken());
        $this->assertFalse($rawLink->hasTargetToken());
    }

    public function testConstructorWithSourceAndAnnotation(): void
    {
        $rawLink = new BeaconRawLink('source', 'annotation');

        $this->assertEquals('source', $rawLink->getSourceToken());
        $this->assertEquals('annotation', $rawLink->getAnnotationToken());
        $this->assertNull($rawLink->getTargetToken());
        $this->assertTrue($rawLink->hasAnnotationToken());
        $this->assertFalse($rawLink->hasTargetToken());
    }

    public function testEmptyTokensReturnFalseForHasMethods(): void
    {
        $rawLink = new BeaconRawLink('source', '', '');

        $this->assertFalse($rawLink->hasAnnotationToken());
        $this->assertFalse($rawLink->hasTargetToken());
    }

    public function testToArray(): void
    {
        $rawLink = new BeaconRawLink('source', 'annotation', 'target');

        $expected = [
            'sourceToken' => 'source',
            'annotationToken' => 'annotation',
            'targetToken' => 'target',
        ];

        $this->assertEquals($expected, $rawLink->toArray());
    }
}
