<?php

namespace App\Tests\Service;

use App\Service\MessengerWorkerStatus;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class MessengerWorkerStatusTest extends TestCase
{
    public function testReportsWhetherARecentHeartbeatExists(): void
    {
        $status = new MessengerWorkerStatus(new ArrayAdapter());

        self::assertFalse($status->isRunning());

        $status->heartbeat();

        self::assertTrue($status->isRunning());
    }
}
