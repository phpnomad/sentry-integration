<?php

namespace PHPNomad\Sentry;

use PHPNomad\Core\Events\ItemLogged;
use PHPNomad\Sentry\Interfaces\SentryCaptureGate;

class DefaultSentryCaptureGate implements SentryCaptureGate
{
    public function shouldCapture(ItemLogged $event): bool
    {
        return $event->severityIs('>=', ItemLogged::WARNING);
    }
}
