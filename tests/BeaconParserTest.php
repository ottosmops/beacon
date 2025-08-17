<?php

declare(strict_types=1);

namespace BeaconParser\Tests;

use BeaconParser\BeaconParser;
use BeaconParser\Exception\BeaconParseException;
use PHPUnit\Framework\TestCase;

class BeaconParserTest extends TestCase
{
    private BeaconParser $parser;

    protected function setUp(): void
    {
        $this->parser = new BeaconParser();
    }

    public function testParseSimpleBeaconFile(): void
    {
        $content = "#FORMAT: BEACON\n" .
                  "#PREFIX: http://example.org/\n" .
                  "#TARGET: http://example.com/\n" .
                  "\n" .
                  "alice\n" .
                  "bob|annotation\n" .
                  "charlie||http://example.net/charlie\n";

        $beaconData = $this->parser->parseString($content);

        $this->assertEquals('http://example.org/', $beaconData->getMetaField('PREFIX'));
        $this->assertEquals('http://example.com/', $beaconData->getMetaField('TARGET'));
        $this->assertEquals(3, $beaconData->getLinkCount());

        $rawLinks = $beaconData->getRawLinks();
        $this->assertEquals('alice', $rawLinks[0]->getSourceToken());
        $this->assertEquals('bob', $rawLinks[1]->getSourceToken());
        $this->assertEquals('annotation', $rawLinks[1]->getAnnotationToken());
        $this->assertEquals('charlie', $rawLinks[2]->getSourceToken());
        $this->assertEquals('http://example.net/charlie', $rawLinks[2]->getTargetToken());
    }

    public function testParseWithoutFormatLine(): void
    {
        $content = "#PREFIX: http://example.org/\n" .
                  "alice\n";

        $beaconData = $this->parser->parseString($content);

        $this->assertEquals('http://example.org/', $beaconData->getMetaField('PREFIX'));
        $this->assertEquals(1, $beaconData->getLinkCount());
    }

    public function testParseWithUnicodeBom(): void
    {
        $content = "\xEF\xBB\xBF#FORMAT: BEACON\n" .
                  "#PREFIX: http://example.org/\n" .
                  "alice\n";

        $beaconData = $this->parser->parseString($content);

        $this->assertEquals('http://example.org/', $beaconData->getMetaField('PREFIX'));
        $this->assertEquals(1, $beaconData->getLinkCount());
    }

    public function testParseWithDifferentLineEndings(): void
    {
        $content = "#FORMAT: BEACON\r\n" .
                  "#PREFIX: http://example.org/\r" .
                  "alice\n";

        $beaconData = $this->parser->parseString($content);

        $this->assertEquals('http://example.org/', $beaconData->getMetaField('PREFIX'));
        $this->assertEquals(1, $beaconData->getLinkCount());
    }

    public function testIgnoreComments(): void
    {
        $content = "#FORMAT: BEACON\n" .
                  "# This is a comment\n" .
                  "#PREFIX: http://example.org/\n" .
                  "# Another comment\n" .
                  "alice\n";

        $beaconData = $this->parser->parseString($content);

        $this->assertEquals('http://example.org/', $beaconData->getMetaField('PREFIX'));
        $this->assertEquals(1, $beaconData->getLinkCount());
    }

    public function testHandleDuplicateMetaFields(): void
    {
        $content = "#FORMAT: BEACON\n" .
                  "#PREFIX: http://example.org/\n" .
                  "#PREFIX: http://duplicate.org/\n" .
                  "alice\n";

        // Should not throw exception but emit warning
        $beaconData = $this->parser->parseString($content);

        // First value should be kept
        $this->assertEquals('http://example.org/', $beaconData->getMetaField('PREFIX'));
    }

    public function testThrowExceptionForMetaFieldAfterLinks(): void
    {
        $content = "#FORMAT: BEACON\n" .
                  "alice\n" .
                  "#PREFIX: http://example.org/\n";

        $this->expectException(BeaconParseException::class);
        $this->expectExceptionMessage('Meta field found after link section');

        $this->parser->parseString($content);
    }

    public function testThrowExceptionForInvalidMetaField(): void
    {
        $content = "#FORMAT: BEACON\n" .
                  "#FIELD WITHOUT COLON\n";  // No colon should be invalid

        $this->expectException(BeaconParseException::class);
        $this->expectExceptionMessage('Invalid meta field format');

        $this->parser->parseString($content);
    }

    public function testSkipEmptySourceTokens(): void
    {
        $content = "#FORMAT: BEACON\n" .
                  "\n" .
                  "   \n" .  // Only whitespace
                  "alice\n";

        $beaconData = $this->parser->parseString($content);

        $this->assertEquals(1, $beaconData->getLinkCount());
        $this->assertEquals('alice', $beaconData->getRawLinks()[0]->getSourceToken());
    }

    public function testConstructedLinks(): void
    {
        $content = "#FORMAT: BEACON\n" .
                  "#PREFIX: http://example.org/\n" .
                  "#TARGET: http://example.com/\n" .
                  "alice\n" .
                  "bob|annotation\n";

        $beaconData = $this->parser->parseString($content);
        $constructedLinks = $beaconData->getConstructedLinks();

        $this->assertCount(2, $constructedLinks);
        $this->assertEquals('http://example.org/alice', $constructedLinks[0]->getSourceIdentifier());
        $this->assertEquals('http://example.com/alice', $constructedLinks[0]->getTargetIdentifier());
        $this->assertEquals('http://example.org/bob', $constructedLinks[1]->getSourceIdentifier());
        $this->assertEquals('http://example.com/bob', $constructedLinks[1]->getTargetIdentifier());
    }

    public function testParseFileNotFound(): void
    {
        $this->expectException(BeaconParseException::class);
        $this->expectExceptionMessage('File not found');

        $this->parser->parseFile('/nonexistent/file.txt');
    }

    public function testCaseInsensitiveMetaFields(): void
    {
        $content = "#FORMAT: BEACON\n" .
                  "#PREFIX: http://example.org/\n" .
                  "#TimeStamp: 2025-08-11T06:32:38+02:00\n" .
                  "#target: http://example.com/{ID}\n" .
                  "#description: Test file\n" .
                  "alice|alice\n";

        $beaconData = $this->parser->parseString($content);

        // All meta fields should be normalized to uppercase
        $this->assertTrue($beaconData->hasMetaField('TIMESTAMP'));
        $this->assertTrue($beaconData->hasMetaField('TARGET'));
        $this->assertTrue($beaconData->hasMetaField('DESCRIPTION'));

        $this->assertEquals('2025-08-11T06:32:38+02:00', $beaconData->getMetaField('TIMESTAMP'));
        $this->assertEquals('http://example.com/{ID}', $beaconData->getMetaField('TARGET'));
        $this->assertEquals('Test file', $beaconData->getMetaField('DESCRIPTION'));
    }
}
