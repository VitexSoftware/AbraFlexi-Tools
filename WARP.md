# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

AbraFlexi-Tools is a collection of command-line utilities for interacting with AbraFlexi ERP servers. The tools are written in PHP and provide functionality for getting/putting data, copying companies, managing webhooks, benchmarking, and generating test data.

## Commands

### Development

```bash
# Install dependencies
composer install

# Run static analysis (PHPStan level 6)
make static-code-analysis

# Fix coding standards (PHP-CS-Fixer with Ergebnis ruleset)
make cs

# Run tests
make tests
```

### Building

```bash
# Build Debian package
make deb

# Create release (requires nextversion variable)
make release nextversion=X.Y.Z
```

### Testing Tools Directly

All tools are in the `bin/` directory. Before running them, ensure you're in the correct directory context:

```bash
# Get record from AbraFlexi
bin/fbget -e adresar -i 666 -u kod nazev

# Put/update record in AbraFlexi
bin/fbput -e adresar -i 333 -u --nazev=Updated

# Copy company between AbraFlexi servers
bin/fbcp https://user:pass@source:5434/c/company https://user:pass@dest:5434/c/company

# Create new company
bin/fbnc new_company_name

# Delete company
bin/fbdc company_to_delete

# Register webhook
bin/fbwh http://webhook.url/endpoint [json|xml]

# Wipe all webhooks
bin/fbwhwipe

# Run benchmark
bin/abraflexi-benchmark -p -c 10 -d 5

# Generate fake addresses
bin/abraflexi-fake-address -i 10
```

## Architecture

### Core Dependencies

- **spojenet/flexibee** (dev-main): PHP library for AbraFlexi API interaction
- **Ease Framework**: Provides shared configuration, logging, and utility functions
- **Faker**: For generating test data

### Script Structure

All executable scripts follow this pattern:
1. Thin wrapper in `bin/` directory that requires the actual implementation
2. Implementation in `src/` directory containing the business logic
3. Scripts use `Ease\Shared::init()` to load configuration from environment or config files

### Configuration

Tools accept configuration via:
- Environment variables: `ABRAFLEXI_URL`, `ABRAFLEXI_LOGIN`, `ABRAFLEXI_PASSWORD`, `ABRAFLEXI_COMPANY`
- JSON config file (default: `/etc/abraflexi/client.json`, override with `-c` option)
- Direct URL with credentials: `https://user:password@host:port/c/company`

Example config file format:
```json
{
    "ABRAFLEXI_URL": "https://demo.abraflexi.eu:5434",
    "ABRAFLEXI_LOGIN": "winstrom",
    "ABRAFLEXI_PASSWORD": "winstrom",
    "ABRAFLEXI_COMPANY": "demo"
}
```

### Key Classes

Scripts primarily use these AbraFlexi classes:
- `AbraFlexi\RO` - Read-only operations
- `AbraFlexi\RW` - Read-write operations
- `AbraFlexi\Company` - Company management
- Evidence-specific classes: `Adresar`, `Banka`, `FakturaVydana`, etc.

### MultiFlexi Integration

The project includes MultiFlexi application descriptors in `multiflexi/*.multiflexi.app.json` that:
- Must conform to: https://raw.githubusercontent.com/VitexSoftware/php-vitexsoftware-multiflexi-core/refs/heads/main/multiflexi.app.schema.json
- Define environment variables, execution parameters, and dependencies
- Enable running tools within the MultiFlexi platform

Validate MultiFlexi JSON files with:
```bash
multiflexi-cli application validate-json --json multiflexi/[filename].app.json
```

## Code Standards

- **PHP Version**: 8.4+ (declare strict_types=1 in all files)
- **Coding Standard**: PSR-12 via Ergebnis PHP-CS-Fixer config (Php81 ruleset)
- **Static Analysis**: PHPStan level 6
- **Documentation**: All functions/classes require docblocks with purpose, parameters, and return types
- **Internationalization**: Use `_()` functions for translatable strings
- **File Headers**: Include standardized license header (see `.php-cs-fixer.dist.php`)

### Specific Rules

- All code comments and messages in English
- Type hints required for all function parameters and return types
- Meaningful variable names (no magic numbers/strings - use constants)
- Proper exception handling with meaningful error messages
- Yoda style disabled (`$var === 'value'` preferred over `'value' === $var`)
- Concat spacing: no spaces (`$a.$b` not `$a . $b`)

## Debian Packaging

Two packages are built:
1. **abraflexi-tools**: Core tools with PHP dependencies
2. **multiflexi-abraflexi-tools**: MultiFlexi integration layer

Vendor directory for Debian builds: `/var/lib/abraflexi-tools`

## Logging

Scripts use the Ease logging system via:
- `EASE_LOGGER` environment variable (e.g., `"console|syslog"`)
- Methods: `addStatusMessage()`, `logBanner()`
- Log levels: info, success, warning, error
