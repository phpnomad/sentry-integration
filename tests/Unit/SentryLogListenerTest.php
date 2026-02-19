<?php

namespace PHPNomad\Sentry\Tests\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPNomad\Core\Events\ItemLogged;
use PHPNomad\Sentry\DefaultSentryCaptureGate;
use PHPNomad\Sentry\Interfaces\SentryCaptureGate;
use PHPNomad\Sentry\Listeners\SentryLogListener;
use PHPUnit\Framework\TestCase;
use Sentry\Breadcrumb;
use Sentry\Event as SentryEvent;
use Sentry\EventId;
use Sentry\Severity;
use Sentry\State\HubInterface;

class SentryLogListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private function makeHub(): Mockery\MockInterface&HubInterface
    {
        return Mockery::mock(HubInterface::class);
    }

    private function makeGate(bool $shouldCapture = true): SentryCaptureGate
    {
        $mock = Mockery::mock(SentryCaptureGate::class);
        $mock->shouldReceive('shouldCapture')->andReturn($shouldCapture);

        return $mock;
    }

    private function makeListener(
        ?HubInterface $hub = null,
        ?SentryCaptureGate $gate = null
    ): SentryLogListener {
        return new SentryLogListener(
            $hub ?? $this->makeHub()->shouldIgnoreMissing(),
            $gate ?? new DefaultSentryCaptureGate()
        );
    }

    public function test_skips_non_item_logged_events()
    {
        $hub = $this->makeHub();
        $hub->shouldNotReceive('captureEvent');
        $hub->shouldNotReceive('captureException');
        $hub->shouldNotReceive('addBreadcrumb');

        $listener = new SentryLogListener($hub, new DefaultSentryCaptureGate());

        $fakeEvent = Mockery::mock(\PHPNomad\Events\Interfaces\Event::class);
        $listener->handle($fakeEvent);
    }

    public function test_captures_event_for_error_severity()
    {
        $hub = $this->makeHub();
        $hub->shouldReceive('captureEvent')
            ->once()
            ->withArgs(function (SentryEvent $event) {
                return $event->getMessage() === 'something broke'
                    && $event->getLevel()->isEqualTo(Severity::error());
            })
            ->andReturn(EventId::generate());

        $listener = new SentryLogListener($hub, new DefaultSentryCaptureGate());

        $listener->handle(new ItemLogged(ItemLogged::ERROR, 'something broke'));
    }

    public function test_captures_event_for_critical_severity_as_fatal()
    {
        $hub = $this->makeHub();
        $hub->shouldReceive('captureEvent')
            ->once()
            ->withArgs(function (SentryEvent $event) {
                return $event->getLevel()->isEqualTo(Severity::fatal());
            })
            ->andReturn(EventId::generate());

        $listener = new SentryLogListener($hub, new DefaultSentryCaptureGate());

        $listener->handle(new ItemLogged(ItemLogged::CRITICAL, 'critical failure'));
    }

    public function test_captures_event_for_warning_severity()
    {
        $hub = $this->makeHub();
        $hub->shouldReceive('captureEvent')
            ->once()
            ->withArgs(function (SentryEvent $event) {
                return $event->getLevel()->isEqualTo(Severity::warning());
            })
            ->andReturn(EventId::generate());

        $listener = new SentryLogListener($hub, new DefaultSentryCaptureGate());

        $listener->handle(new ItemLogged(ItemLogged::WARNING, 'a warning'));
    }

    public function test_captures_exception_when_in_context()
    {
        $exception = new \RuntimeException('db connection failed');

        $hub = $this->makeHub();
        $hub->shouldReceive('captureException')
            ->once()
            ->with($exception)
            ->andReturn(EventId::generate());

        $listener = new SentryLogListener($hub, new DefaultSentryCaptureGate());

        $listener->handle(new ItemLogged(ItemLogged::ERROR, 'database error', ['exception' => $exception]));
    }

    public function test_adds_breadcrumb_for_info_severity()
    {
        $hub = $this->makeHub();
        $hub->shouldReceive('addBreadcrumb')
            ->once()
            ->withArgs(function (Breadcrumb $breadcrumb) {
                return $breadcrumb->getMessage() === 'user logged in'
                    && $breadcrumb->getLevel() === Breadcrumb::LEVEL_INFO;
            })
            ->andReturn(true);
        $hub->shouldNotReceive('captureEvent');
        $hub->shouldNotReceive('captureException');

        $listener = new SentryLogListener($hub, new DefaultSentryCaptureGate());

        $listener->handle(new ItemLogged(ItemLogged::INFO, 'user logged in'));
    }

    public function test_adds_breadcrumb_for_debug_severity()
    {
        $hub = $this->makeHub();
        $hub->shouldReceive('addBreadcrumb')
            ->once()
            ->withArgs(function (Breadcrumb $breadcrumb) {
                return $breadcrumb->getLevel() === Breadcrumb::LEVEL_DEBUG;
            })
            ->andReturn(true);
        $hub->shouldNotReceive('captureEvent');

        $listener = new SentryLogListener($hub, new DefaultSentryCaptureGate());

        $listener->handle(new ItemLogged(ItemLogged::DEBUG, 'debug info'));
    }

    public function test_does_not_throw_on_sentry_failure()
    {
        $hub = $this->makeHub();
        $hub->shouldReceive('captureEvent')
            ->once()
            ->andThrow(new \RuntimeException('Sentry SDK exploded'));

        $listener = new SentryLogListener($hub, new DefaultSentryCaptureGate());

        // Should not throw
        $listener->handle(new ItemLogged(ItemLogged::ERROR, 'test'));
    }

    public function test_sets_context_as_extras_on_captured_event()
    {
        $context = ['user_id' => 42, 'action' => 'checkout'];

        $hub = $this->makeHub();
        $hub->shouldReceive('captureEvent')
            ->once()
            ->withArgs(function (SentryEvent $event) use ($context) {
                return $event->getExtra() === $context;
            })
            ->andReturn(EventId::generate());

        $listener = new SentryLogListener($hub, new DefaultSentryCaptureGate());

        $listener->handle(new ItemLogged(ItemLogged::ERROR, 'checkout failed', $context));
    }

    public function test_custom_gate_controls_capture_vs_breadcrumb()
    {
        // Gate says don't capture — should breadcrumb instead
        $hub = $this->makeHub();
        $hub->shouldReceive('addBreadcrumb')->once()->andReturn(true);
        $hub->shouldNotReceive('captureEvent');

        $listener = new SentryLogListener($hub, $this->makeGate(false));

        $listener->handle(new ItemLogged(ItemLogged::ERROR, 'suppressed error'));
    }
}
