<?php

namespace PHPNomad\Sentry;

use PHPNomad\Core\Events\ItemLogged;
use PHPNomad\Events\Interfaces\HasListeners;
use PHPNomad\Loader\Interfaces\HasClassDefinitions;
use PHPNomad\Sentry\Interfaces\SentryCaptureGate;
use PHPNomad\Sentry\Listeners\SentryLogListener;
use Sentry\State\HubInterface;

class SentryInitializer implements HasListeners, HasClassDefinitions
{
    public function getClassDefinitions(): array
    {
        return [
            SentryLogListener::class => SentryLogListener::class,
            DefaultSentryCaptureGate::class => SentryCaptureGate::class,
            DsnConfiguredHub::class => HubInterface::class,
        ];
    }

    public function getListeners(): array
    {
        return [
            ItemLogged::class => SentryLogListener::class,
        ];
    }
}
