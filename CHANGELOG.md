# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.2] - 2025-01-01

### Enhanced
- Comprehensive test coverage with 56 tests and 111 assertions
- Enhanced webhook verification with dual signature support (HMAC + RSA)
- Improved error handling and exception management
- Updated dependencies with PSR logging support
- Enhanced documentation with complete usage examples
- Expanded currency support (MYR, SGD, USD, THB, VND, IDR)
- Modern PHP 8.0+ compatibility

### Added
- New test files for comprehensive component coverage
- Enhanced mock data for testing webhook scenarios
- Improved composer.json with proper dependency management
- Updated README with detailed configuration examples

### Fixed
- Resolved namespace conflicts in test suite
- Fixed memory exhaustion issues during testing
- Improved code quality and consistency

## [1.0.1] - 2025-08-19

### Enhanced
- Improved SDK compatibility with official Chip-in Asia integration
- Enhanced error handling and validation
- Better webhook verification implementation
- Optimized request/response handling
- Updated test coverage for all components

### Fixed
- Minor improvements in code structure and documentation
- Enhanced parameter validation

## [1.0.0] - 2025-08-18

### Added
- Initial stable release of Omnipay ChipInAsia payment gateway
- Support for purchase transactions
- Support for complete purchase (payment verification)
- Webhook handling and verification
- Exception handling for API errors
- Comprehensive test suite
- PHP 7.4+ and PHP 8.x support
- Laravel integration examples
- Complete documentation and usage examples

### Features
- **Gateway Integration**: Full integration with Chip-in Asia payment API
- **Purchase Flow**: Create payment requests with redirect to Chip-in Asia
- **Payment Completion**: Verify and complete payments after customer return
- **Webhook Support**: Handle real-time payment notifications
- **Error Handling**: Comprehensive exception handling for various error scenarios
- **Multi-Currency**: Support for MYR, SGD and other supported currencies
- **Test Mode**: Built-in test mode for development and testing

### Security
- Webhook signature verification
- Secure API key handling
- Input validation and sanitization

### Documentation
- Complete README with usage examples
- Laravel integration guide
- API reference documentation
- Configuration options reference

[1.0.2]: https://github.com/sitehandy/omnipay-chipinasia/releases/tag/v1.0.2
[1.0.1]: https://github.com/sitehandy/omnipay-chipinasia/releases/tag/v1.0.1
[1.0.0]: https://github.com/sitehandy/omnipay-chipinasia/releases/tag/v1.0.0