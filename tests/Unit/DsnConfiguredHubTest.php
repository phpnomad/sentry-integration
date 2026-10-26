<?php

namespace PHPNomad\Sentry\Tests\Unit;

use PHPNomad\Sentry\DsnConfiguredHub;
use PHPNomad\Sentry\Interfaces\SentryClientOptionsProvider;
use PHPNomad\Sentry\Interfaces\SentryDsnProvider;
use PHPUnit\Framework\TestCase;
use Sentry\Event;

class DsnConfiguredHubTest extends TestCase
{
    private const DSN = 'https://public@o0.ingest.sentry.io/1';

    public function test_hub_without_dsn_has_no_client()
    {
        $hub = new DsnConfiguredHub($this->dsnProvider(''));

        $this->assertNull($hub->getClient());
    }

    public function test_client_does_not_send_default_pii_by_default()
    {
        $hub = new DsnConfiguredHub($this->dsnProvider(self::DSN));

        $this->assertNotNull($hub->getClient());
        $this->assertFalse($hub->getClient()->getOptions()->shouldSendDefaultPii());
    }

    public function test_client_uses_options_from_a_dsn_provider_that_supplies_them()
    {
        $beforeSend = static fn(Event $event): ?Event => null;

        $provider = new class (self::DSN, $beforeSend) implements SentryDsnProvider, SentryClientOptionsProvider {
            /** @var callable */
            private $beforeSend;

            public function __construct(private string $dsn, callable $beforeSend)
            {
                $this->beforeSend = $beforeSend;
            }

            public function getDsn(): string
            {
                return $this->dsn;
            }

            public function getClientOptions(): array
            {
                return [
                    'dsn' => 'https://ignored@o0.ingest.sentry.io/2',
                    'environment' => 'staging',
                    'release' => 'abc123',
                    'before_send' => $this->beforeSend,
                ];
            }
        };

        $options = (new DsnConfiguredHub($provider))->getClient()->getOptions();

        $this->assertSame('staging', $options->getEnvironment());
        $this->assertSame('abc123', $options->getRelease());
        $this->assertSame($beforeSend, $options->getBeforeSendCallback());
        $this->assertFalse($options->shouldSendDefaultPii());
        $this->assertSame('1', (string) $options->getDsn()->getProjectId());
    }

    public function test_host_can_override_the_default_options()
    {
        $options = DsnConfiguredHub::buildOptions(
            new class implements SentryDsnProvider, SentryClientOptionsProvider {
                public function getDsn(): string
                {
                    return '';
                }

                public function getClientOptions(): array
                {
                    return ['send_default_pii' => true];
                }
            },
            self::DSN
        );

        $this->assertTrue($options['send_default_pii']);
        $this->assertSame(self::DSN, $options['dsn']);
    }

    private function dsnProvider(string $dsn): SentryDsnProvider
    {
        return new class ($dsn) implements SentryDsnProvider {
            public function __construct(private string $dsn)
            {
            }

            public function getDsn(): string
            {
                return $this->dsn;
            }
        };
    }
}
