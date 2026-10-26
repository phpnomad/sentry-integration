<?php

namespace PHPNomad\Sentry;

use PHPNomad\Sentry\Interfaces\SentryClientOptionsProvider;
use PHPNomad\Sentry\Interfaces\SentryDsnProvider;
use Sentry\ClientBuilder;
use Sentry\State\Hub;

class DsnConfiguredHub extends Hub
{
    /**
     * Options every client starts from. A host can override them through
     * {@see SentryClientOptionsProvider}.
     */
    public const DEFAULT_OPTIONS = [
        'send_default_pii' => false,
    ];

    public function __construct(SentryDsnProvider $dsnProvider)
    {
        $dsn = $dsnProvider->getDsn();

        if (!empty($dsn)) {
            try {
                $client = ClientBuilder::create(self::buildOptions($dsnProvider, $dsn))->getClient();
                parent::__construct($client);
            } catch (\Throwable) {
                parent::__construct();
            }
        } else {
            parent::__construct();
        }
    }

    /**
     * The defaults, then the host's options, then the DSN.
     *
     * @return array<string, mixed>
     */
    public static function buildOptions(SentryDsnProvider $dsnProvider, string $dsn): array
    {
        $options = $dsnProvider instanceof SentryClientOptionsProvider
            ? $dsnProvider->getClientOptions()
            : [];

        return array_merge(self::DEFAULT_OPTIONS, $options, ['dsn' => $dsn]);
    }
}
