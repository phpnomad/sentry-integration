# phpnomad/sentry-integration

[![Latest Version](https://img.shields.io/packagist/v/phpnomad/sentry-integration.svg)](https://packagist.org/packages/phpnomad/sentry-integration)
[![Total Downloads](https://img.shields.io/packagist/dt/phpnomad/sentry-integration.svg)](https://packagist.org/packages/phpnomad/sentry-integration)
[![PHP Version](https://img.shields.io/packagist/php-v/phpnomad/sentry-integration.svg)](https://packagist.org/packages/phpnomad/sentry-integration)
[![License](https://img.shields.io/packagist/l/phpnomad/sentry-integration.svg)](https://packagist.org/packages/phpnomad/sentry-integration)

Integrates Sentry with PHPNomad's logging. A listener subscribes to `ItemLogged` events from `phpnomad/core`, forwards WARNING and above to Sentry as captured events, and records lower-severity entries as breadcrumbs so they appear as context on the next error. Severity mapping, capture rules, and DSN sourcing are all override points.

## Installation

```bash
composer require phpnomad/sentry-integration
```

## What this provides

- A listener that forwards `ItemLogged` events to Sentry, with automatic severity mapping from PHPNomad log levels to Sentry severities.
- A `SentryCaptureGate` interface with a default implementation that captures WARNING and above. Override it to change what becomes an event versus a breadcrumb.
- A `SentryDsnProvider` interface so the host application supplies the DSN from its own configuration source (environment variables, secrets manager, config file).

## Requirements

- `phpnomad/core` (provides the `ItemLogged` event)
- `phpnomad/event` and `phpnomad/loader` for listener registration
- `sentry/sentry` `^4.0`
- PHP 8.2 or later

## Usage

Implement `SentryDsnProvider` with your application's DSN source, bind it in an initializer, and register `SentryInitializer` in your loader chain.

```php
use PHPNomad\Loader\Interfaces\HasClassDefinitions;
use PHPNomad\Sentry\Interfaces\SentryDsnProvider;

class EnvDsnProvider implements SentryDsnProvider
{
    public function getDsn(): string
    {
        return $_ENV['SENTRY_DSN'] ?? '';
    }
}

class AppSentryInitializer implements HasClassDefinitions
{
    public function getClassDefinitions(): array
    {
        return [EnvDsnProvider::class => SentryDsnProvider::class];
    }
}
```

Add `SentryInitializer` and `AppSentryInitializer` to your loader. From that point forward, any `$logger->error()` or `$logger->critical()` call is forwarded to Sentry without further application changes. If the DSN is empty the hub no-ops, and all Sentry calls are wrapped in try/catch so monitoring never crashes the host app.

## Documentation

Framework docs live at [phpnomad.com](https://phpnomad.com). For Sentry client configuration, see the [Sentry PHP SDK documentation](https://docs.sentry.io/platforms/php/).

## License

MIT. See [LICENSE](LICENSE).
