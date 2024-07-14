<?php

declare(strict_types=1);

namespace Aikeedo\CurrencyBeacon;

use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Override;
use Presentation\RequestHandlers\Admin\AbstractAdminViewRequestHandler;
use Presentation\Response\ViewResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Settings request handler.
 * 
 * This request handler is responsible for displaying the settings page for the 
 * Currency Beacon rate provider plugin.
 * 
 * Request handlers must implement the RequestHandlerInterface. Here we extend
 * the AbstractAdminViewRequestHandler which is a base class for admin view
 * request handlers.
 * 
 * Currently the route of the setting{s page for the currency rate providers must
 * be match /settings/rate-providers/{key} pattern. The {key} is the lookup key
 * of the rate provider (CurrencyBeacon::LOOKUP_KEY).
 * 
 * You can set completely different route patterns for the settings page. However,
 * at the Settings > Ecxhange Rates page, app expects the settings page to be
 * displayed at /settings/rate-providers/{key} path.
 */
#[Route(path: '/settings/rate-providers/currency-beacon', method: RequestMethod::GET)]
class SettingsRequestHandler extends AbstractAdminViewRequestHandler implements
    RequestHandlerInterface
{
    #[Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Here we return a new ViewResponse with the template path to render.
        // @currency-beacon is the namespace we've added in the Plugin class.
        return new ViewResponse('@currency-beacon/settings.twig');
    }
}
