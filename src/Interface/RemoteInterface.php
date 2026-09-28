<?php

namespace Wexample\SymfonyRemote\Interface;

use Wexample\SymfonyRemote\Class\RemoteStatus;

/**
 * Something the app talks to outside itself — an HTTP API, an SDK, a binary. A
 * service implementing this interface is registered as a remote whatever its
 * transport: autoconfiguration tags it, and RemoteRegistry collects it.
 */
interface RemoteInterface
{
    public const string TAG = 'wexample_symfony_remote.remote';

    /**
     * Unique among the app's remotes; what commands and screens name it by.
     */
    public function getKey(): string;

    public function getLabel(): string;

    /**
     * Asks the remote whether it answers. May throw: the registry turns a
     * failure into a Down status, so an implementation need not catch.
     */
    public function checkStatus(): RemoteStatus;
}
