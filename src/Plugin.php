<?php

declare(strict_types=1);

namespace Aikeedo\CurrencyBeacon;

use Billing\Infrastructure\Currency\RateProviderCollectionInterface;
use Easy\Router\Mapper\AttributeMapper;
use Override;
use Plugin\Domain\Context;
use Plugin\Domain\PluginInterface;
use Twig\Loader\FilesystemLoader;

class Plugin implements PluginInterface
{
    /**
     * Plugin constructor. Inject required dependencies for the plugin.
     *
     * @param FilesystemLoader $loader TWIG loader.
     * @param AttributeMapper $mapper Route mapper. 
     * @param RateProviderCollectionInterface $collection Rate provider collection.
     */
    public function __construct(
        private FilesystemLoader $loader,
        private AttributeMapper $mapper,
        private RateProviderCollectionInterface $collection,
    ) {
    }

    #[Override]
    public function boot(Context $context): void
    {
        // Add template path to the TWIG loader to scan for view templates 
        // in current directory. The first argument is the path to the directory,
        // the second argument is the namespace to use in the template.
        $this->loader->addPath(__DIR__, 'currency-beacon');

        // Add path to the router mapper to scan for routes 
        // in current directory
        $this->mapper->addPath(__DIR__);

        // Add rate provider to the collection
        $this->collection
            ->add(CurrencyBeacon::LOOKUP_KEY, CurrencyBeacon::class);
    }
}
