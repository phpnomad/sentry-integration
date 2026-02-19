<?php

namespace PHPNomad\Sentry;

use PHPNomad\Sentry\Interfaces\SentryDsnProvider;
use Sentry\ClientBuilder;
use Sentry\State\Hub;

class DsnConfiguredHub extends Hub
{
    public function __construct(SentryDsnProvider $dsnProvider)
    {
        $dsn = $dsnProvider->getDsn();

        if (!empty($dsn)) {
            try {
                $client = ClientBuilder::create(['dsn' => $dsn])->getClient();
                parent::__construct($client);
            } catch (\Throwable) {
                parent::__construct();
            }
        } else {
            parent::__construct();
        }
    }
}
