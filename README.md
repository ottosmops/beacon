[![CI](https://github.com/ottosmops/beacon/actions/workflows/ci.yml/badge.svg)](https://github.com/ottosmops/beacon/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/ottosmops/beacon/branch/main/graph/badge.svg)](https://codecov.io/gh/ottosmops/beacon)
[![Latest Stable Version](https://poser.pugx.org/ottosmops/beacon/v)](https://packagist.org/packages/ottosmops/beacon)
[![Total Downloads](https://poser.pugx.org/ottosmops/beacon/downloads)](https://packagist.org/packages/ottosmops/beacon)
[![License](https://poser.pugx.org/ottosmops/beacon/license)](https://packagist.org/packages/ottosmops/beacon)
[![PHP Version Require](https://poser.pugx.org/ottosmops/beacon/require/php)](https://packagist.org/packages/ottosmops/beacon)

# BEACON Parser

A PHP library for parsing BEACON files. 

**The package was heavily constructed with AI assistence. Please doublecheck if you want to use it in production.**

## Overview

BEACON is a data interchange format for large numbers of uniform links. A BEACON file contains meta fields and links that can be used to create connections between different datasets.

This library provides a PHP implementation for parsing BEACON files according to the [BEACON specification](https://gbv.github.io/beaconspec/beacon.html).

## Features

- Parse BEACON files from strings or file paths
- Extract meta fields and link data
- Support for all BEACON meta fields (PREFIX, TARGET, MESSAGE, RELATION, etc.)
- Link construction with URI pattern expansion
- Validation of BEACON file format

## Installation

Install via Composer:

```bash
composer require ottosmops/beacon
```

## Quick Start

### Basic Usage

```php
use BeaconParser\BeaconParser;

// Parse from file
$parser = new BeaconParser();
$beaconData = $parser->parseFile('path/to/beacon.txt');

// Parse from string
$beaconContent = file_get_contents('beacon.txt');
$beaconData = $parser->parseString($beaconContent);

// Access meta fields
$description = $beaconData->getMetaField('DESCRIPTION');
$prefix = $beaconData->getMetaField('PREFIX');

// Access links
$links = $beaconData->getLinks();
foreach ($links as $link) {
    echo $link->getSourceIdentifier() . ' -> ' . $link->getTargetIdentifier() . "\n";
}
```

### Working with Links

```php
// Get constructed links with full URIs
$links = $beaconData->getConstructedLinks();

foreach ($links as $link) {
    echo "Source: " . $link->getSourceIdentifier() . "\n";
    echo "Target: " . $link->getTargetIdentifier() . "\n";
    echo "Relation: " . $link->getRelationType() . "\n";
    if ($link->hasAnnotation()) {
        echo "Annotation: " . $link->getAnnotation() . "\n";
    }
    echo "---\n";
}
```

## BEACON Format

BEACON files consist of:

1. Format indicator (`#FORMAT: BEACON`)
2. Meta fields (lines starting with `#`)
3. Empty lines (optional)
4. Link lines

### Example BEACON File

```beacon
#FORMAT: BEACON
#PREFIX: http://example.org/
#TARGET: http://example.com/
#DESCRIPTION: Example link dump
#CREATOR: Example Organization

alice|About Alice
bob||http://example.net/bob
charlie|Charlie's Page|http://example.net/charlie
```

## Meta Fields

The library supports all standard BEACON meta fields:

### Link Construction Fields

- `PREFIX` - URI pattern for source identifiers
- `TARGET` - URI pattern for target identifiers  
- `MESSAGE` - Default link annotation
- `RELATION` - Relation type for links
- `ANNOTATION` - Meaning of link annotations

### Link Dump Description Fields

- `DESCRIPTION` - Human readable description
- `CREATOR` - Creator name or URI
- `CONTACT` - Contact information
- `HOMEPAGE` - Website with additional information
- `FEED` - Download URL
- `TIMESTAMP` - Last modification date
- `UPDATE` - Update frequency

### Dataset Fields

- `SOURCESET` - Source dataset URI
- `TARGETSET` - Target dataset URI
- `NAME` - Target dataset name
- `INSTITUTION` - Responsible organization

## Link Construction

The library automatically constructs full URIs from link tokens using the meta fields:

- Source identifiers are constructed using the `PREFIX` pattern
- Target identifiers are constructed using the `TARGET` pattern
- Default values are applied when tokens are missing
- URI patterns support `{ID}` and `{+ID}` expansions

## Validation

The library includes a validator to check BEACON files.

### Using the Validator

```php
use BeaconParser\BeaconValidator;

$validator = new BeaconValidator();

// Validate from file
$result = $validator->validateFile('beacon.txt');

// Validate from string
$result = $validator->validateString($beaconContent);

// Check if valid
if ($result->isValid()) {
    echo "BEACON file is valid!\n";
} else {
    echo "BEACON file has errors:\n";
    foreach ($result->getErrors() as $error) {
        echo "- $error\n";
    }
}

// Show detailed report
echo $result->getDetailedReport();
```

### CLI Validation Tool

A command-line validation tool is included that supports both local files and URLs:

```bash
# Validate a local BEACON file
php bin/validate-beacon.php example.beacon

# Validate a BEACON file from URL
php bin/validate-beacon.php https://example.org/beacon.txt

# Validate with full path
php bin/validate-beacon.php /path/to/beacon/file.txt

# Show help
php bin/validate-beacon.php --help
```

The CLI tool automatically detects whether the input is a URL (starting with `http://` or `https://`) or a local file path and handles the validation accordingly.

#### URL Validation Features

- **Automatic URL Detection**: Recognizes HTTP/HTTPS URLs automatically
- **Secure Downloads**: Uses proper HTTP headers and timeout handling
- **Error Handling**: Provides clear error messages for HTTP errors (404, 500, etc.)
- **Content Type Support**: Accepts text/plain and other text formats

### Validation Features

The validator checks for:

- **File Structure**: Format indicator, proper line endings, UTF-8 encoding
- **Meta Fields**: Known fields, proper syntax, valid values
- **URI Validation**: Valid URIs in appropriate fields
- **Link Construction**: Proper token handling and URI building
- **Best Practices**: Recommended fields, HTTPS usage, proper formatting
- **Specification Compliance**: Full adherence to BEACON specification

Exit codes:

- `0`: File is valid
- `1`: File has validation errors
- `2`: File not found or other error

## Usage Examples

### CLI Usage Examples

```bash
# Validate local files
php bin/validate-beacon.php example.beacon
php bin/validate-beacon.php /path/to/beacon/file.txt

# Validate remote files from URLs
php bin/validate-beacon.php https://example.org/beacon.txt
php bin/validate-beacon.php http://data.example.com/links.beacon

# Show detailed help
php bin/validate-beacon.php --help
```

### Programming Examples

### Example BEACON File

```beacon
## Examples

### Sample BEACON File

```beacon
#FORMAT: BEACON
#PREFIX: http://example.org/people/
#TARGET: http://example.com/documents/
#DESCRIPTION: Example BEACON file mapping people to documents
#CREATOR: Example Organization
#RELATION: http://purl.org/dc/elements/1.1/contributor

alice
bob|author
charlie|editor|special-doc
diana||http://example.net/external-doc
```

### Parsing Example

```php
use BeaconParser\BeaconParser;

$parser = new BeaconParser();
$beaconData = $parser->parseFile('example.beacon');

// Get meta information
echo "Description: " . $beaconData->getMetaField('DESCRIPTION') . "\n";
echo "Creator: " . $beaconData->getMetaField('CREATOR') . "\n";

// Process links
foreach ($beaconData->getConstructedLinks() as $link) {
    echo $link->getSourceIdentifier() . " -> " . $link->getTargetIdentifier() . "\n";
    
    if ($link->hasAnnotation()) {
        echo "  Annotation: " . $link->getAnnotation() . "\n";
    }
}
```

### Expected Output

```text
Description: Example BEACON file mapping people to documents
Creator: Example Organization
http://example.org/people/alice -> http://example.com/documents/alice
http://example.org/people/bob -> http://example.com/documents/bob
  Annotation: author
http://example.org/people/charlie -> http://example.com/documents/special-doc
  Annotation: editor  
http://example.org/people/diana -> http://example.net/external-doc
```

### Validation Examples

```php
use BeaconParser\BeaconValidator;

$validator = new BeaconValidator();

// Validate a local file
$result = $validator->validateFile('beacon.txt');

// Validate content from a URL programmatically
$content = file_get_contents('https://example.org/beacon.txt');
$result = $validator->validateString($content);

// Check validation results
if ($result->isValid()) {
    echo "✅ BEACON file is valid\n";
} else {
    echo "❌ BEACON file has errors:\n";
    foreach ($result->getErrors() as $error) {
        echo "  - $error\n";
    }
}

// Show detailed report
echo $result->getDetailedReport();
```

## Requirements

- PHP 8.0 or higher

## Development

### Running Tests

```bash
composer test
```

### Code Style

```bash
composer phpcs
```

### Static Analysis

```bash
composer phpstan
```

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Links

- [BEACON Specification](https://gbv.github.io/beaconspec/beacon.html)
- [Wikipedia:BEACON](https://de.wikipedia.org/wiki/Wikipedia:BEACON)
