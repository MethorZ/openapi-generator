# Changelog

All notable changes to `methorz/openapi-generator` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned for v0.3.0
- Configurable output customization
- Additional Symfony Validator attributes
- OpenAPI security scheme generation
- Request/response examples generation

## [0.2.0] - 2024-11-26

### Added - Phase 2: Core Enhancements
- **Enum Support**: Full support for PHP 8.1+ backed and unit enums in schemas
- **Nested DTO Support**: Automatic schema generation for nested DTO references with `$ref`
- **Collection Support**: Typed arrays with PHPDoc `@param array<Type>` annotations
- **Union Type Support**: Generates `oneOf` schemas for PHP 8.0+ union types
- **Nullable Handling**: Improved nullable type detection and schema generation
- **Circular Reference Detection**: Prevents infinite loops when analyzing DTOs
- **Schema Caching**: Performance optimization with schema caching and `getAllSchemas()` method
- **Class Resolution**: Smart namespace resolution for short class names in PHPDoc

### Improved
- Enhanced PHPDoc parsing for constructor parameters (property promotion support)
- Better type detection for nested DTOs, enums, and collections
- More robust schema generation with fallback handling

### Testing
- Added 8 new comprehensive tests for enhanced features (23 tests total, 95 assertions)
- Test fixtures for enums, nested DTOs, and complex scenarios
- 100% test pass rate maintained

## [0.1.0] - 2024-11-25

### Added - Phase 1: Quality Foundation
- Initial release with core functionality
- OpenAPI 3.0 specification generation
- Automatic route scanning from application configuration
- DTO schema generation with reflection-based analysis
- Symfony Validator attribute support:
  - NotBlank (required fields)
  - Length (min/max string length)
  - Range (min/max numeric values)
  - Email (email format)
  - Url (URL format)
  - Uuid (UUID format)
- Handler analysis for automatic request/response DTO detection
- Console command for specification generation (`generate:openapi`)
- YAML and JSON output formats
- PHPStan Level 9 type safety
- Initial test suite (15 tests, 54 assertions)
- PSR-12 coding standards
- Complete documentation (README, CONTRIBUTING, SECURITY)
- GitHub Actions CI/CD workflow
- PHP 8.2+ support
