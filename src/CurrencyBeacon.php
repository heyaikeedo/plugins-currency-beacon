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

/**
 * Currency Beacon rate provider.
 * 
 * This is the main class of the Currency Beacon rate provider plugin. It 
 * implements the RateProviderInterface and provides the exchange rate between
 * two currencies.
 * 
 * All currency rate providers must implement the RateProviderInterface. 
 * Depending on the plugin functionality, plugin can implement additional 
 * interfaces or extend other classes.
 */
class CurrencyBeacon implements RateProviderInterface
{
    /**
     * The lookup key for the Currency Beacon rate provider.
     * 
     * We'll this key to register and lookup the rate provider with the rate 
     * provider collection.
     * 
     * Key also will be used as a path parameter for the RequestHandler.
     */
    public const LOOKUP_KEY = 'currency-beacon';

    /**
     * Constructs a new CurrencyBeacon instance.
     *
     * @param Dispatcher $dispatcher The event dispatcher.
     * @param Client $client The HTTP client.
     * @param string|null $updatedAt The last update timestamp of the rates.
     * @param array|null $rates The currency rates.
     */
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

    /**
     * Get the currency rates. If the rates are not available or outdated,
     * fetch the rates from the API.
     *
     * @return array The currency rates.
     */
    private function getRates(): array
    {
        if (
            $this->rates
            && $this->updatedAt
            && $this->updatedAt + 3600 * 4 >= time() // Update every 4 hours
        ) {
            return $this->rates;
        }

        $rates = $this->fetchRates();

        // Save new data date
        $cmd = new SaveOptionCommand(
            'currency_beacon', // Unique key for the option
            json_encode(
                [
                    'updated_at' => time(),
                    'rates' => $rates,
                ]
            )
        );

        // Save the option to the database
        $this->dispatcher->dispatch($cmd);

        return $rates;
    }

    /**
     * Fetch the currency rates from the API.
     *
     * @return array The currency rates.
     * @throws RuntimeException If failed to fetch currency rates.
     */
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
