<?php

namespace Wexample\SymfonyRemote\Enum;

enum RemoteState: string
{
    case Up = 'up';
    case Down = 'down';

    /**
     * Credentials or address missing: a configuration problem, not an outage,
     * so nothing should page anyone for it.
     */
    case Unconfigured = 'unconfigured';
}
