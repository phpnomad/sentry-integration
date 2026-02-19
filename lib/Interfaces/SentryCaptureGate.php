<?php

namespace PHPNomad\Sentry\Interfaces;

use PHPNomad\Core\Events\ItemLogged;

interface SentryCaptureGate
{
    public function shouldCapture(ItemLogged $event): bool;
}
