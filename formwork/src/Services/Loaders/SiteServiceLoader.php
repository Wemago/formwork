<?php

namespace Formwork\Services\Loaders;

use Formwork\Cms\Site;
use Formwork\Config\Config;
use Formwork\Services\Container;
use Formwork\Services\ResolutionAwareServiceLoaderInterface;

final class SiteServiceLoader implements ResolutionAwareServiceLoaderInterface
{
    public function __construct(
        private Config $config,
    ) {
    }

    public function load(Container $container): Site
    {
        $config = $this->config->get('site');

        return $container->build(Site::class, ['data' => [
            ...$config,
            'contentPath' => $this->config->get('system.pages.path'),
        ]]);
    }

    /**
     * @param Site $service
     */
    public function onResolved(object $service, Container $container): void
    {
        $service->load();
    }
}
