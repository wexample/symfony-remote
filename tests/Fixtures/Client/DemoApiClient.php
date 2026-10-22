<?php

namespace Wexample\SymfonyRemote\Tests\Fixtures\Client;

use Wexample\PhpApi\Common\Client;

class DemoApiClient extends Client
{
    public const string PING_PATH = 'health';
}
