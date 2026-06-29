<?php
namespace Clicalmani\Foundation\Messenger\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

class RetryingStamp implements StampInterface
{
    public function __construct(
        public readonly int $retryCount
    ) {}
}