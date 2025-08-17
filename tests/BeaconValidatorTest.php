<?php

declare(strict_types=1);

namespace BeaconParser\Tests;

use PHPUnit\Framework\TestCase;
use BeaconParser\BeaconValidator;
use BeaconParser\BeaconValidationResult;

class BeaconValidatorTest extends TestCase
{
    private BeaconValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new BeaconValidator();
    }

    public function testValidateValidBeaconFile(): void
    {
        $content = "#FORMAT: BEACON\n" .
                  "#PREFIX: http://example.org/\n" .
                  "#TARGET: http://example.com/\n" .
                  "#DESCRIPTION: Test file\n" .
                  "#CREATOR: Test Creator\n" .
                  "\n" .
                  "alice\n" .
                  "bob|annotation\n";

        $result = $this->validator->validateString($content);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testValidateInvalidTimestamp(): void
    {
        $content = "#FORMAT: BEACON\n" .
                  "#PREFIX: http://example.org/\n" .
                  "#TIMESTAMP: invalid-date\n" .
                  "alice|http://example.com/alice\n";

        $result = $this->validator->validateString($content);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertCount(1, $errors);
        $this->assertStringContainsString("Invalid TIMESTAMP format", $errors[0]);
    }

    public function testValidateValidTimestamp(): void
    {
        $content = "#FORMAT: BEACON\n" .
                  "#PREFIX: http://example.org/\n" .
                  "#TIMESTAMP: 2024-08-09\n" .
                  "alice|http://example.com/alice\n";

        $result = $this->validator->validateString($content);

        $this->assertTrue($result->isValid());
    }

    public function testValidateInvalidUpdateField(): void
    {
        $content = "#FORMAT: BEACON\n" .
                  "#UPDATE: invalid-frequency\n" .
                  "alice\n";

        $result = $this->validator->validateString($content);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString("Invalid UPDATE value", $errors[0]);
    }

    public function testValidateValidUpdateField(): void
    {
        $content = "#FORMAT: BEACON\n" .
                  "#PREFIX: http://example.org/\n" .
                  "#UPDATE: daily\n" .
                  "alice|http://example.com/alice\n";

        $result = $this->validator->validateString($content);

        $this->assertTrue($result->isValid());
    }

    public function testValidateUnknownMetaField(): void
    {
        $content = "#FORMAT: BEACON\n" .
                  "#PREFIX: http://example.org/\n" .
                  "#UNKNOWN: value\n" .
                  "alice|http://example.com/alice\n";

        $result = $this->validator->validateString($content);

        $this->assertTrue($result->isValid()); // Unknown fields are warnings, not errors
        $warnings = $result->getWarnings();
        $this->assertNotEmpty($warnings);
        $this->assertStringContainsString("Unknown meta field: UNKNOWN", $warnings[0]);
    }

    public function testValidateInvalidUri(): void
    {
        $content = "#FORMAT: BEACON\n" .
                  "#HOMEPAGE: not-a-valid-uri\n" .
                  "alice\n";

        $result = $this->validator->validateString($content);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertStringContainsString("Invalid URI in HOMEPAGE", $errors[0]);
    }

    public function testValidateNoLinksWarning(): void
    {
        $content = "#FORMAT: BEACON\n" .
                  "#PREFIX: http://example.org/\n";

        $result = $this->validator->validateString($content);

        $this->assertTrue($result->isValid()); // No links is a warning, not error
        $warnings = $result->getWarnings();
        $this->assertStringContainsString("No links found", $warnings[0]);
    }

    public function testValidateParseError(): void
    {
        $content = "#FORMAT: BEACON\n" .
                  "#PREFIX: http://example.org/\n" .
                  "alice|http://example.com/alice\n" .
                  "#INVALID: after links\n";  // Meta field after links should cause parse error

        $result = $this->validator->validateString($content);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertStringContainsString("Parse error", $errors[0]);
    }

    public function testValidateFileNotFound(): void
    {
        $result = $this->validator->validateFile('/nonexistent/file.txt');

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertStringContainsString("File not found", $errors[0]);
    }

    public function testValidationResultMethods(): void
    {
        $result = new BeaconValidationResult(['error1'], ['warning1'], ['info1']);

        $this->assertFalse($result->isValid());
        $this->assertEquals(['error1'], $result->getErrors());
        $this->assertEquals(['warning1'], $result->getWarnings());
        $this->assertEquals(['info1'], $result->getInfo());
        $this->assertEquals(2, $result->getIssueCount());

        $result->addError('error2');
        $result->addWarning('warning2');
        $result->addInfo('info2');

        $this->assertEquals(2, count($result->getErrors()));
        $this->assertEquals(2, count($result->getWarnings()));
        $this->assertEquals(2, count($result->getInfo()));
    }

    public function testValidationResultSummary(): void
    {
        $result = new BeaconValidationResult();
        $this->assertStringContainsString('BEACON file is valid', $result->getSummary());

        $result->addError('Test error');
        $this->assertStringContainsString('BEACON file has errors', $result->getSummary());
    }

    public function testValidationResultDetailedReport(): void
    {
        $result = new BeaconValidationResult();
        $result->addError("Test error");
        $result->addWarning("Test warning");
        $result->addInfo("Test info");

        $report = $result->getDetailedReport();

        $this->assertStringContainsString("❌ BEACON file has errors", $report);
        $this->assertStringContainsString("Test error", $report);
        $this->assertStringContainsString("Test warning", $report);
        $this->assertStringContainsString("Test info", $report);
    }

    public function testMetaFieldNamingWarnings(): void
    {
        $content = "#FORMAT: BEACON
" .
                  "#PREFIX: http://example.org/
" .
                  "#TimeStamp: 2025-08-11T06:32:38+02:00
" .
                  "#target: http://example.com/{ID}
" .
                  "alice|http://example.com/alice
";

        $result = $this->validator->validateString($content);

        $this->assertTrue($result->isValid()); // Should still be valid

        $warnings = $result->getWarnings();
        $this->assertCount(2, $warnings); // TimeStamp and target warnings

        $this->assertStringContainsString("Meta field '#TimeStamp:' on line 3 uses non-standard casing", $warnings[0]);
        $this->assertStringContainsString("Meta field '#target:' on line 4 uses non-standard casing", $warnings[1]);
        $this->assertStringContainsString("METAFIELD = +( %x41-5A )", $warnings[0]);
    }
}
