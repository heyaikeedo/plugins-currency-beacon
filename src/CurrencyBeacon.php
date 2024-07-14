<?php

declare(strict_types=1);

namespace Aikeedo\CurrencyBeacon;

use Billing\Infrastructure\Currency\RateProviderInterface;
use Easy\Container\Attributes\Inject;
use Option\Application\Commands\SaveOptionCommand;
use Override;
use RuntimeException;
use Shared\Domain\ValueObjects\CurrencyCode;
use Shared\Infrastructure\CommandBus\Dispatcher;

class CurrencyBeacon implements RateProviderInterface
{
    public const LOOKUP_KEY = 'currency-beacon';

    public function __construct(
        private Dispatcher $dispatcher,
        private Client $client,

        #[Inject('option.currency_beacon.updated_at')]
        private ?string $updatedAt = null,

        #[Inject('option.currency_beacon.rates')]
        private ?array $rates = null,
    ) {
    }

    #[Override]
    public function getName(): string
    {
        return 'Currency Beacon';
    }

    #[Override]
    public function getRate(CurrencyCode $from, CurrencyCode $to): int|float
    {
        $rates = $this->getRates();
        return $rates[$to->value] / $rates[$from->value];
    }

    private function getRates(): array
    {
        if (
            $this->rates
            && $this->updatedAt
            && $this->updatedAt + 3600 * 4 >= time()
        ) {
            return $this->rates;
        }

        $rates = $this->fetchRates();

        // Save new data date
        $cmd = new SaveOptionCommand(
            'currency-beacon',
            json_encode(
                [
                    'updated_at' => time(),
                    'rates' => $rates,
                ]
            )
        );

        $this->dispatcher->dispatch($cmd);

        return $rates;
    }

    private function fetchRates(): array
    {
        $response = $this->client->sendRequest('GET', '/v1/latest', params: [
            'base' => 'USD'
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException('Failed to fetch currency rates');
        }

        $body = json_decode($response->getBody()->getContents());

        $rates = [];
        foreach ($body->response->rates as $code => $rate) {
            $rates[$code] = $rate;
        }

        return $rates;
    }
}
