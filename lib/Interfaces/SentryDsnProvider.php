<?php

namespace PHPNomad\Sentry\Interfaces;

interface SentryDsnProvider
{
    public function getDsn(): string;
}
