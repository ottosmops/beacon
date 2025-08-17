<?php

declare(strict_types=1);

namespace BeaconParser;

use BeaconParser\Exception\BeaconParseException;

/**
 * Validation result containing validation status and any found issues
 */
class BeaconValidationResult
{
    /** @var string[] */
    private array $errors;

    /** @var string[] */
    private array $warnings;

    /** @var string[] */
    private array $info;

    /**
     * @param string[] $errors
     * @param string[] $warnings
     * @param string[] $info
     */
    public function __construct(array $errors = [], array $warnings = [], array $info = [])
    {
        $this->errors = $errors;
        $this->warnings = $warnings;
        $this->info = $info;
    }

    /**
     * Check if validation passed (no errors)
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Get all validation errors
     *
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get all validation warnings
     *
     * @return string[]
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get all validation info messages
     *
     * @return string[]
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    /**
     * Add an error message
     */
    public function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    /**
     * Add a warning message
     */
    public function addWarning(string $message): void
    {
        $this->warnings[] = $message;
    }

    /**
     * Add an info message
     */
    public function addInfo(string $message): void
    {
        $this->info[] = $message;
    }

    /**
     * Get total number of issues (errors + warnings)
     */
    public function getIssueCount(): int
    {
        return count($this->errors) + count($this->warnings);
    }

    /**
     * Generate a summary report
     */
    public function getSummary(): string
    {
        $summary = [];

        if ($this->isValid()) {
            $summary[] = "✅ BEACON file is valid";
        } else {
            $summary[] = "❌ BEACON file has errors";
        }

        $summary[] = sprintf(
            "Errors: %d, Warnings: %d, Info: %d",
            count($this->errors),
            count($this->warnings),
            count($this->info)
        );

        return implode("\n", $summary);
    }

    /**
     * Generate a detailed report
     */
    public function getDetailedReport(): string
    {
        $report = [$this->getSummary(), ""];

        if (!empty($this->errors)) {
            $report[] = "ERRORS:";
            foreach ($this->errors as $error) {
                $report[] = "  - " . $error;
            }
            $report[] = "";
        }

        if (!empty($this->warnings)) {
            $report[] = "WARNINGS:";
            foreach ($this->warnings as $warning) {
                $report[] = "  - " . $warning;
            }
            $report[] = "";
        }

        if (!empty($this->info)) {
            $report[] = "INFO:";
            foreach ($this->info as $info) {
                $report[] = "  - " . $info;
            }
            $report[] = "";
        }

        return implode("\n", $report);
    }
}
