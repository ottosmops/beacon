<?php

declare(strict_types=1);

namespace BeaconParser\Tests;

use BeaconParser\BeaconData;
use BeaconParser\BeaconRawLink;
use PHPUnit\Framework\TestCase;

class BeaconDataTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $metaFields = ['PREFIX' => 'http://example.org/', 'TARGET' => 'http://example.com/'];
        $rawLinks = [new BeaconRawLink('alice'), new BeaconRawLink('bob')];

        $beaconData = new BeaconData($metaFields, $rawLinks);

        $this->assertEquals($metaFields, $beaconData->getMetaFields());
        $this->assertEquals($rawLinks, $beaconData->getRawLinks());
        $this->assertEquals(2, $beaconData->getLinkCount());
    }

    public function testMetaFieldOperations(): void
    {
        $beaconData = new BeaconData();

        $this->assertNull($beaconData->getMetaField('PREFIX'));
        $this->assertFalse($beaconData->hasMetaField('PREFIX'));

        $beaconData->setMetaField('PREFIX', 'http://example.org/');

        $this->assertEquals('http://example.org/', $beaconData->getMetaField('PREFIX'));
        $this->assertTrue($beaconData->hasMetaField('PREFIX'));
    }

    public function testAddRawLink(): void
    {
        $beaconData = new BeaconData();
        $rawLink = new BeaconRawLink('alice');

        $beaconData->addRawLink($rawLink);

        $this->assertEquals(1, $beaconData->getLinkCount());
        $this->assertEquals([$rawLink], $beaconData->getRawLinks());
    }

    public function testToArray(): void
    {
        $metaFields = ['PREFIX' => 'http://example.org/'];
        $rawLinks = [new BeaconRawLink('alice')];
        $beaconData = new BeaconData($metaFields, $rawLinks);

        $array = $beaconData->toArray();

        $this->assertArrayHasKey('metaFields', $array);
        $this->assertArrayHasKey('rawLinks', $array);
        $this->assertArrayHasKey('constructedLinks', $array);
        $this->assertEquals($metaFields, $array['metaFields']);
    }
}
