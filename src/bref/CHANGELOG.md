# Change Log

## 1.0.0

### Added

- Add support for Symfony 8

## 0.4.0

### Added

- Add support for Symfony 7

## 0.3.1

### Fixed

- [Console] Make sure to restart process if there is an uncaught exception

## 0.3.0

### BC breaks

We do no longer require `bref/bref`. If you use Symfony/Laravel Kernel or PSR-15
you will get an exception. Run `composer require bref/bref` to solve the issue.

### Added

- We do not use internal classes in Bref any more (https://github.com/php-runtime/runtime/pull/88)
- Some handlers do not require the `bref/bref` package (https://github.com/php-runtime/runtime/pull/89)
- We include a runtime specific Bref layer (https://github.com/php-runtime/bref-layer)

### Fixed

- Fixed Trusted proxies configuration

## 0.2.2

### Fixed

- Make sure to restart process if there is an uncaught exception

## 0.2.1

### Added

- Support for Bref 1.3

## 0.2.0

### Added

- `vendor/bin/bref-local-handler.php` to invoke PSR-11 handlers locally
- Invocation and request context to the Request ServerBag

### Fixed

- Session handling for Symfony 5.4 and up
- Error handling on invalid `_HANDLER` string

## 0.1.1

### Added

- Add support for Symfony 6

### Fixed

- Local console output when running command on Lambda

## 0.1.0

First version
