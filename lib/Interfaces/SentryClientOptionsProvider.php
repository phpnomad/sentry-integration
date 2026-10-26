<?php

namespace PHPNomad\Sentry\Interfaces;

/**
 * Supplies the Sentry client options the hub is built with.
 *
 * Implement it on the same class that implements {@see SentryDsnProvider}.
 * The hub reads the options from the DSN provider when it implements this
 * interface, so no extra binding or load order is needed.
 *
 * The options are the ones `Sentry\ClientBuilder::create()` accepts, such as
 * `before_send`, `before_breadcrumb`, `environment` and `release`. The DSN
 * always comes from {@see SentryDsnProvider::getDsn()}, so a `dsn` key here is
 * ignored.
 */
interface SentryClientOptionsProvider
{
    /**
     * @return array<string, mixed>
     */
    public function getClientOptions(): array;
}
