<?php

namespace Wexample\SymfonyRemote\Tests\Unit\Service;

use LogicException;
use PHPUnit\Framework\TestCase;
use Wexample\SymfonyRemote\Enum\RemoteState;
use Wexample\SymfonyRemote\Service\RemoteRegistry;
use Wexample\SymfonyRemote\Tests\Fixtures\Remote\BrokenRemote;
use Wexample\SymfonyRemote\Tests\Fixtures\Remote\ReachableRemote;

class RemoteRegistryTest extends TestCase
{
    public function testRemotesAreIndexedByKey(): void
    {
        $registry = new RemoteRegistry([new ReachableRemote(), new BrokenRemote()]);

        $this->assertSame(['reachable', 'broken'], array_keys($registry->all()));
        $this->assertInstanceOf(BrokenRemote::class, $registry->get('broken'));
    }

    public function testAThrowingRemoteIsDownWithoutHidingTheOthers(): void
    {
        $statuses = (new RemoteRegistry([new BrokenRemote(), new ReachableRemote()]))->checkAll();

        $this->assertSame(RemoteState::Down, $statuses['broken']->state);
        $this->assertSame('Connection refused', $statuses['broken']->message);
        $this->assertSame(RemoteState::Up, $statuses['reachable']->state);
    }

    public function testTheRegistryMeasuresLatency(): void
    {
        $status = (new RemoteRegistry([new ReachableRemote()]))->check('reachable');

        $this->assertNotNull($status->latency);
        $this->assertGreaterThanOrEqual(0.0, $status->latency);
    }

    public function testDuplicateKeysAreRejected(): void
    {
        $this->expectException(LogicException::class);

        new RemoteRegistry([new ReachableRemote(), new ReachableRemote()]);
    }

    public function testAnUnknownKeyNamesTheRegisteredOnes(): void
    {
        $this->expectExceptionMessage('Registered: reachable.');

        (new RemoteRegistry([new ReachableRemote()]))->get('missing');
    }
}
