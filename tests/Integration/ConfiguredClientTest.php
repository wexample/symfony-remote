<?php

namespace Wexample\SymfonyRemote\Tests\Integration;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Wexample\PhpRemote\Class\RemoteRegistry;
use Wexample\PhpRemote\Enum\RemoteState;
use Wexample\SymfonyRemote\Tests\Fixtures\Client\DemoApiClient;

class ConfiguredClientTest extends KernelTestCase
{
    private MockHandler $handler;

    protected function setUp(): void
    {
        $this->handler = self::getContainer()->get('test.mock_handler');
    }

    public function testTheClientIsBuiltFromItsConfiguration(): void
    {
        $client = self::getContainer()->get(DemoApiClient::class);
        $this->handler->append(new Response(200));

        $client->get('things');

        $request = $this->handler->getLastRequest();
        $this->assertSame('https://demo.test/things', (string) $request->getUri());
        $this->assertSame('Bearer secret', $request->getHeaderLine('Authorization'));
        $this->assertSame('yes', $request->getHeaderLine('X-Test'));
        $this->assertFalse($request->hasHeader('X-Empty'));
        $this->assertSame(30.0, $this->handler->getLastOptions()['timeout']);
    }

    public function testAnAnsweringClientIsUp(): void
    {
        $this->handler->append(new Response(200));

        $status = $this->registry()->check('demo');

        $this->assertSame(RemoteState::Up, $status->state);
        $this->assertSame('/health', $this->handler->getLastRequest()->getUri()->getPath());
        $this->assertSame('Demo API', $this->registry()->get('demo')->getLabel());
    }

    public function testAFailingClientIsDownWithTheReason(): void
    {
        // One retry is configured: both attempts fail.
        $this->handler->append(new Response(503), new Response(503));

        $status = $this->registry()->check('demo');

        $this->assertSame(RemoteState::Down, $status->state);
        $this->assertStringContainsString('HTTP 503', $status->message);
    }

    public function testAClientMissingItsUrlIsUnconfigured(): void
    {
        $status = $this->registry()->check('unset_url');

        $this->assertSame(RemoteState::Unconfigured, $status->state);
        $this->assertSame('Missing: base_url.', $status->message);
        $this->assertCount(0, $this->handler);
    }

    public function testTheSharedQuotaHoldsRequestsBack(): void
    {
        $client = self::getContainer()->get('wexample_symfony_remote.client.limited');
        $this->handler->append(new Response(200), new Response(200));

        $start = microtime(true);
        $client->get('first');
        $client->get('second');

        // One request per two-second window: the second waits for the next one.
        $this->assertGreaterThan(0.5, microtime(true) - $start);
    }

    private function registry(): RemoteRegistry
    {
        return self::getContainer()->get(RemoteRegistry::class);
    }
}
