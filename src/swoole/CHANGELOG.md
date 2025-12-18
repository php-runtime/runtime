# Change Log

## 1.0.0

### Added

- Add support for Symfony 8

## 0.4.0

- Add support for Symfony 7
- Drop support for PHP 7 and PHP 8.0

## 0.3.1

### Fixed

- Buffer each StreamedResponse chunk when sending data to swoole worker output

## 0.3.0

### Added

- Support for kernel.terminate Symfony event
- Binary file and streamed response

### Fixed

- Overwritten headers in Swoole response

## 0.2.1

### Added

- Option for SWOOLE_SOCK_TCP

### Fixed

- Response status code
- Session handling for Symfony 5.4 and up.

## 0.2.0

### Added

- Support for Swoole's Server mode
- Support for more settings

## 0.1.1

### Fixed

- Fixed case of SERVER variables and added request headers

### Added

- Add support for Symfony 6

## 0.1.0

First version
