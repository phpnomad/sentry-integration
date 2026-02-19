<?php

namespace PHPNomad\Sentry\Listeners;

use PHPNomad\Core\Events\ItemLogged;
use PHPNomad\Events\Interfaces\CanHandle;
use PHPNomad\Events\Interfaces\Event;
use PHPNomad\Sentry\Interfaces\SentryCaptureGate;
use PHPNomad\Sentry\Interfaces\SentryDsnProvider;
use Sentry\Breadcrumb;
use Sentry\ClientBuilder;
use Sentry\Event as SentryEvent;
use Sentry\Severity;
use Sentry\State\Hub;
use Sentry\State\HubInterface;

/** @implements CanHandle<ItemLogged> */
class SentryLogListener implements CanHandle
{
    private const SEVERITY_MAP = [
        ItemLogged::DEBUG => 'debug',
        ItemLogged::INFO => 'info',
        ItemLogged::NOTICE => 'info',
        ItemLogged::WARNING => 'warning',
        ItemLogged::ERROR => 'error',
        ItemLogged::CRITICAL => 'fatal',
        ItemLogged::ALERT => 'fatal',
        ItemLogged::EMERGENCY => 'fatal',
    ];

    private const BREADCRUMB_LEVEL_MAP = [
        ItemLogged::DEBUG => Breadcrumb::LEVEL_DEBUG,
        ItemLogged::INFO => Breadcrumb::LEVEL_INFO,
        ItemLogged::NOTICE => Breadcrumb::LEVEL_INFO,
        ItemLogged::WARNING => Breadcrumb::LEVEL_WARNING,
        ItemLogged::ERROR => Breadcrumb::LEVEL_ERROR,
        ItemLogged::CRITICAL => Breadcrumb::LEVEL_FATAL,
        ItemLogged::ALERT => Breadcrumb::LEVEL_FATAL,
        ItemLogged::EMERGENCY => Breadcrumb::LEVEL_FATAL,
    ];

    private ?HubInterface $hub = null;
    private bool $initialized = false;

    public function __construct(
        protected SentryDsnProvider $dsnProvider,
        protected SentryCaptureGate $captureGate
    ) {
    }

    /** @internal For testing only */
    public function setHub(HubInterface $hub): void
    {
        $this->hub = $hub;
    }

    public function handle(Event $event): void
    {
        if (!$event instanceof ItemLogged) {
            return;
        }

        try {
            if (!$this->ensureInitialized()) {
                return;
            }

            if ($this->captureGate->shouldCapture($event)) {
                $this->capture($event);
            } else {
                $this->addBreadcrumb($event);
            }
        } catch (\Throwable) {
            // Monitoring must never crash the app
        }
    }

    private function ensureInitialized(): bool
    {
        if ($this->initialized) {
            return $this->hub !== null;
        }

        $this->initialized = true;

        if ($this->hub !== null) {
            return true;
        }

        $dsn = $this->dsnProvider->getDsn();

        if (empty($dsn)) {
            return false;
        }

        try {
            $client = ClientBuilder::create(['dsn' => $dsn])->getClient();
            $this->hub = new Hub($client);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function capture(ItemLogged $event): void
    {
        $context = $event->getContext();

        if (isset($context['exception']) && $context['exception'] instanceof \Throwable) {
            $this->hub->captureException($context['exception']);

            return;
        }

        $sentryEvent = SentryEvent::createEvent();
        $sentryEvent->setMessage($event->getMessage());
        $sentryEvent->setLevel($this->mapSeverity($event->getSeverity()));

        if (!empty($context)) {
            $sentryEvent->setExtra($context);
        }

        $this->hub->captureEvent($sentryEvent);
    }

    private function addBreadcrumb(ItemLogged $event): void
    {
        $level = self::BREADCRUMB_LEVEL_MAP[$event->getSeverity()] ?? Breadcrumb::LEVEL_INFO;

        $this->hub->addBreadcrumb(new Breadcrumb(
            $level,
            Breadcrumb::TYPE_DEFAULT,
            'log',
            $event->getMessage(),
            $event->getContext()
        ));
    }

    private function mapSeverity(int $severity): Severity
    {
        $name = self::SEVERITY_MAP[$severity] ?? 'info';

        return new Severity($name);
    }
}
