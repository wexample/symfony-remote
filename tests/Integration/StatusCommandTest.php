<?php

namespace Wexample\SymfonyRemote\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class StatusCommandTest extends KernelTestCase
{
    public function testAReachableRemoteSucceeds(): void
    {
        $tester = $this->runStatus(['key' => 'reachable']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('up', $tester->getDisplay());
        $this->assertStringContainsString('Reachable', $tester->getDisplay());
    }

    public function testAnUnconfiguredRemoteDoesNotFail(): void
    {
        $tester = $this->runStatus(['key' => 'unset_url']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('Missing: base_url.', $tester->getDisplay());
    }

    public function testADownRemoteFailsAndReportsInJson(): void
    {
        $tester = $this->runStatus(['key' => 'broken', '--format' => 'json']);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());

        $report = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('broken', $report[0]['key']);
        $this->assertSame('down', $report[0]['state']);
        $this->assertSame('Connection refused', $report[0]['message']);
    }

    public function testCheckingEveryRemoteFailsWhenOneIsDown(): void
    {
        $tester = $this->runStatus([]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());

        foreach (['reachable', 'broken', 'demo', 'unset_url', 'limited'] as $key) {
            $this->assertStringContainsString($key, $tester->getDisplay());
        }
    }

    /**
     * @param array<string, string> $input
     */
    private function runStatus(array $input): CommandTester
    {
        $tester = new CommandTester((new Application(self::bootKernel()))->find('remote:status'));
        $tester->execute($input);

        return $tester;
    }
}
