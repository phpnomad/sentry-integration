<?php

namespace PHPNomad\Sentry\Tests\Unit;

use PHPNomad\Core\Events\ItemLogged;
use PHPNomad\Sentry\DefaultSentryCaptureGate;
use PHPUnit\Framework\TestCase;

class DefaultSentryCaptureGateTest extends TestCase
{
    private DefaultSentryCaptureGate $gate;

    protected function setUp(): void
    {
        $this->gate = new DefaultSentryCaptureGate();
    }

    public function test_captures_warning_and_above()
    {
        $this->assertTrue($this->gate->shouldCapture(new ItemLogged(ItemLogged::WARNING, 'test')));
        $this->assertTrue($this->gate->shouldCapture(new ItemLogged(ItemLogged::ERROR, 'test')));
        $this->assertTrue($this->gate->shouldCapture(new ItemLogged(ItemLogged::CRITICAL, 'test')));
        $this->assertTrue($this->gate->shouldCapture(new ItemLogged(ItemLogged::ALERT, 'test')));
        $this->assertTrue($this->gate->shouldCapture(new ItemLogged(ItemLogged::EMERGENCY, 'test')));
    }

    public function test_does_not_capture_below_warning()
    {
        $this->assertFalse($this->gate->shouldCapture(new ItemLogged(ItemLogged::DEBUG, 'test')));
        $this->assertFalse($this->gate->shouldCapture(new ItemLogged(ItemLogged::INFO, 'test')));
        $this->assertFalse($this->gate->shouldCapture(new ItemLogged(ItemLogged::NOTICE, 'test')));
    }
}
