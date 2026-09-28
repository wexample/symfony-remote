<?php

namespace Wexample\SymfonyRemote\Tests\Fixtures\Remote;

use RuntimeException;
use Wexample\SymfonyRemote\Class\RemoteStatus;
use Wexample\SymfonyRemote\Interface\RemoteInterface;

class BrokenRemote implements RemoteInterface
{
    public function getKey(): string
    {
        return 'broken';
    }

    public function getLabel(): string
    {
        return 'Broken';
    }

    public function checkStatus(): RemoteStatus
    {
        throw new RuntimeException('Connection refused');
    }
}
