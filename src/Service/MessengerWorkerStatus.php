<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;

#[AsEventListener(event: WorkerStartedEvent::class, method: 'onWorkerStarted')]
#[AsEventListener(event: WorkerRunningEvent::class, method: 'onWorkerRunning')]
final readonly class MessengerWorkerStatus
{
    private const string CACHE_KEY = 'messenger_worker_last_heartbeat';
    private const int HEARTBEAT_TTL = 90;

    public function __construct(
        #[Autowire(service: 'cache.app')]
        private CacheItemPoolInterface $cache,
    ) {
    }

    public function onWorkerStarted(WorkerStartedEvent $event): void
    {
        $this->heartbeat();
    }

    public function onWorkerRunning(WorkerRunningEvent $event): void
    {
        $this->heartbeat();
    }

    public function isRunning(): bool
    {
        $heartbeat = $this->cache->getItem(self::CACHE_KEY);
        $timestamp = $heartbeat->get();

        return is_int($timestamp) && $timestamp >= time() - self::HEARTBEAT_TTL;
    }

    public function heartbeat(): void
    {
        $heartbeat = $this->cache->getItem(self::CACHE_KEY);
        $heartbeat->set(time());
        $heartbeat->expiresAfter(self::HEARTBEAT_TTL);
        $this->cache->save($heartbeat);
    }
}
