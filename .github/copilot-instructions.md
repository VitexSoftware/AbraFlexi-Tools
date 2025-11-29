---
description: AbraFlexi Tools - collection of utilities and tools for AbraFlexi accounting system
applyTo: '**'
---

# AbraFlexi Tools - Copilot Instructions

## Project Overview
AbraFlexi Tools is a **comprehensive utilities collection** for AbraFlexi accounting system:
- **Data Manipulation**: Tools for data import/export and transformation
- **Reporting Tools**: Custom report generators and formatters
- **Synchronization**: Data sync utilities between systems
- **Maintenance Tools**: Database cleanup and optimization utilities
- **MultiFlexi Integration**: Built using VitexSoftware MultiFlexi ecosystem

## 📋 Development Standards

### Core Coding Guidelines
- **PHP 8.4+**: Use modern PHP features and strict types: `declare(strict_types=1);`
- **PSR-12**: Follow PHP-FIG coding standards for consistency
- **Type Safety**: Include type hints for all parameters and return types
- **Documentation**: PHPDoc blocks for all public methods and classes
- **Testing**: PHPUnit tests for all new functionality
- **Internationalization**: Use `_()` functions for translatable strings

### Code Quality Requirements
- **Syntax Validation**: After every PHP file edit, run `php -l filename.php` for syntax checking
- **Error Handling**: Implement comprehensive try-catch blocks with meaningful error messages
- **Testing**: Create/update PHPUnit test files for all new/modified classes
- **Performance**: Optimize for production use with large datasets
- **Security**: Ensure code doesn't expose sensitive AbraFlexi credentials

### Development Best Practices
- **Code Comments**: Write in English using complete sentences and proper grammar
- **Variable Names**: Use meaningful names that describe their purpose
- **Constants**: Avoid magic numbers/strings; define constants instead
- **Exception Handling**: Always provide meaningful error messages
- **Commit Messages**: Use imperative mood and keep them concise
- **Security**: Ensure code is secure and doesn't expose sensitive information
- **Compatibility**: Maintain compatibility with latest PHP and library versions
- **Maintainability**: Follow best practices for maintainable code

### Working Directory Requirements
- **Scripts Execution**: Always change to `src/` directory before running scripts
  ```bash
  cd src/
  php script-name.php
  ```
- **Path Resolution**: Ensures proper relative path resolution for dependencies
- **Consistent Environment**: Maintains consistent execution environment

### MultiFlexi Integration Guidelines
- **Schema Compliance**: All MultiFlexi JSON files must conform to official schemas
- **Application Config** (`multiflexi/*.app.json`): 
  https://raw.githubusercontent.com/VitexSoftware/php-vitexsoftware-multiflexi-core/refs/heads/main/multiflexi.app.schema.json
- **Report Output**: 
  https://raw.githubusercontent.com/VitexSoftware/php-vitexsoftware-multiflexi-core/refs/heads/main/multiflexi.report.schema.json

### AbraFlexi Integration Requirements
- **API Authentication**: Secure authentication with AbraFlexi systems
- **Data Privacy**: Handle sensitive financial data appropriately
- **Large Dataset Handling**: Optimize tools for processing large amounts of accounting data
- **Transaction Safety**: Ensure data integrity in all operations

### Testing Requirements
- **PHPUnit Integration**: All new classes require corresponding test files
- **Test Coverage**: Aim for comprehensive test coverage of all functionality
- **Mock AbraFlexi**: Use mocks for AbraFlexi API during testing
- **Tool Validation**: Test each tool with sample data

## Example Workflow
```bash
# 1. Navigate to source directory
cd src/

# 2. Run specific tool
php data-export.php

# 3. Validate output against schema
multiflexi-cli application validate-json --file ../multiflexi/tools.app.json

# 4. Test changes
cd ..
vendor/bin/phpunit tests/

# 5. Syntax validation
php -l src/new-tool.php
```

⚠️ **Important Notes for Copilot:**
- This is a **utilities collection** - each tool should be standalone and focused
- **Data integrity** is critical when manipulating accounting data
- **Performance optimization** is essential for large AbraFlexi datasets
- Follow **MultiFlexi ecosystem patterns** for consistency
- **Working directory matters** - always use `src/` directory for script execution
