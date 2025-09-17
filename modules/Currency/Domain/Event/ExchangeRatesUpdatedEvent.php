<?php

declare(strict_types=1);

namespace Modules\Currency\Domain\Event;

use DateTimeImmutable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExchangeRatesUpdatedEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, float>  $rates
     */
    public function __construct(
        public readonly array $rates,
        public readonly string $baseCurrency,
        public readonly string $provider,
        public readonly DateTimeImmutable $timestamp,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('exchange-rates'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'rates.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'rates' => $this->rates,
            'base_currency' => $this->baseCurrency,
            'provider' => $this->provider,
            'timestamp' => $this->timestamp->format('c'),
        ];
    }
}
