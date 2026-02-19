# PHPNomad Sentry Integration

Sentry error monitoring integration for PHPNomad applications. Transparently captures errors and exceptions via the PHPNomad logging system — no code changes needed in your application.

## Installation

```bash
composer require phpnomad/sentry-integration
```

## Setup

### 1. Implement `SentryDsnProvider`

Create a class that returns your Sentry DSN:

```php
use PHPNomad\Sentry\Interfaces\SentryDsnProvider;

class MySentryDsnProvider implements SentryDsnProvider
{
    public function getDsn(): string
    {
        return $_ENV['SENTRY_DSN'] ?? '';
    }
}
```

### 2. Register initializers

Add the Sentry initializer and your DSN provider binding to your application boot:

```php
use PHPNomad\Sentry\SentryInitializer;

// In your initializer chain:
new SentryInitializer(),
new MyDsnProviderInitializer(), // binds MySentryDsnProvider => SentryDsnProvider
```

### 3. Done

Any `$logger->error()`, `$logger->critical()`, etc. calls will now be captured in Sentry. Lower-severity logs (debug, info, notice) are added as breadcrumbs that appear as context on the next error.

## How It Works

- Listens to `ItemLogged` events broadcast by PHPNomad's logger
- WARNING and above: captured as Sentry events
- Below WARNING: added as Sentry breadcrumbs
- If `$context['exception']` contains a `Throwable`, uses `captureException()` for full stack traces
- Lazy initialization: Sentry SDK is only initialized on the first log event
- Empty DSN gracefully no-ops (no errors if Sentry isn't configured)
- All Sentry calls wrapped in try/catch — monitoring never crashes your app

## Customizing the Capture Gate

Override `SentryCaptureGate` to control what gets captured vs breadcrumbed:

```php
use PHPNomad\Core\Events\ItemLogged;
use PHPNomad\Sentry\Interfaces\SentryCaptureGate;

class MyCaptureGate implements SentryCaptureGate
{
    public function shouldCapture(ItemLogged $event): bool
    {
        // Only capture ERROR and above (skip warnings)
        return $event->severityIs('>=', ItemLogged::ERROR);
    }
}
```

Bind it in a later initializer to override the default:

```php
return [MyCaptureGate::class => SentryCaptureGate::class];
```

## Severity Mapping

| PHPNomad Level | Sentry Severity |
|----------------|-----------------|
| DEBUG          | debug           |
| INFO           | info            |
| NOTICE         | info            |
| WARNING        | warning         |
| ERROR          | error           |
| CRITICAL       | fatal           |
| ALERT          | fatal           |
| EMERGENCY      | fatal           |

## License

MIT
