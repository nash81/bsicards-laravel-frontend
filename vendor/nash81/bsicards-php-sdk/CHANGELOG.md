# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-03-18

### Added

- **Digital Visa Wallet Cards** — New category of virtual Visa cards with 3DS auto-approve, Google Pay, and Apple Pay support
  - `visaWalletCreateVirtualCard($email, $firstName, $lastName)` — Create a new virtual Visa wallet card
  - `visaWalletGetAllCards($email)` — List all Digital Visa Wallet cards for a user
  - `visaWalletGetCard($email, $cardId)` — Get full card details (number, CVV, expiry, transactions)
  - `visaWalletFundCard($email, $cardId, $amount)` — Fund a card (minimum $5.00)
  - `visaWalletGetOTP($email, $cardId)` — Retrieve OTP for GPay/Apple Pay provisioning
  - `visaWalletBlockCard($email, $cardId)` — Block (freeze) a card
  - `visaWalletUnblockCard($email, $cardId)` — Unblock (unfreeze) a card
- Updated API documentation (`docs/API.md`) with full Digital Visa Wallet section
- Updated examples documentation (`docs/EXAMPLES.md`) with Digital Visa Wallet code samples
- Updated README with Digital Visa Wallet features and API method reference

## [1.0.0] - 2026-03-06

### Added

- Initial stable release of BSICARDS PHP SDK (`v1.0.0`)
- Full support for MasterCard issuance and management
- Full support for Visa card issuance and management
- Full support for Digital Wallet card creation and management
- Card creation, retrieval, and listing endpoints
- Card transaction history retrieval
- Card management (freeze, unfreeze, PIN change)
- Card funding functionality
- Laravel 5.5+ service provider auto-discovery
- Environment-based configuration (via .env)
- Comprehensive error handling with APIException
- Full PHPDoc documentation
- Type hints for all methods (PHP 8.1+)
- GuzzleHTTP 7.0+ integration
- Composer package support
- MIT License

### Features

- 30+ API endpoints
- Support for multiple card types (MasterCard, Visa, Digital Wallet)
- Secure credential management
- Auto-discovery for Laravel
- PSR-4 autoloading
- Extensive documentation and examples

